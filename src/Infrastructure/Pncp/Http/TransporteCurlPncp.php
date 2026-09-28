<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Semae\Pca\Infrastructure\Pncp\Observabilidade\AcaoTecnicaPncp;
use Throwable;

final class TransporteCurlPncp implements TransporteHttpPncp
{
    private int $ordemHttp = 0;

    public function __construct(
        private readonly ConfiguracaoTransportePncp $configuracao,
        private readonly ExecutorCurlPncp $executor,
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function enviar(RequisicaoHttpPncp $requisicao): RespostaHttpPncp
    {
        $this->validarRuntimeCurl();

        $corpoRequisicao = $requisicao->getCorpo();

        if (
            $corpoRequisicao !== null
            && strlen($corpoRequisicao) > $this->configuracao->getLimitePayloadBytes()
        ) {
            throw FalhaTransportePncpException::limitePayloadExcedido();
        }

        /** @var array<string, string[]> $headersResposta */
        $headersResposta = [];
        $corpoResposta = '';
        $limiteRespostaExcedido = false;
        $houveRespostaHttp = false;
        $cabecalhosRespostaBrutos = '';
        $ordemHttp = ++$this->ordemHttp;

        $opcoes = $this->montarOpcoes(
            $requisicao,
            $headersResposta,
            $corpoResposta,
            $limiteRespostaExcedido,
            $houveRespostaHttp,
            $cabecalhosRespostaBrutos
        );
        $inicio = hrtime(true);
        $resultado = null;
        $categoriaFalha = 'TRANSPORTE_LOCAL';
        try {
            $resultado = $this->executor->executar(
                $this->configuracao->getBaseUrl() . $requisicao->getCaminho(),
                $opcoes
            );

            if ($limiteRespostaExcedido) {
                $categoriaFalha = 'LIMITE_RESPOSTA';
                throw FalhaTransportePncpException::limiteRespostaExcedido();
            }

            if (!$resultado->foiExecutado() || $resultado->getErrno() !== 0) {
                $categoriaFalha = 'CURL';
                throw FalhaTransportePncpException::execucaoCurlFalhou($resultado->getErrno());
            }

            if ($resultado->getResultadoVerificacaoTls() !== 0) {
                $categoriaFalha = 'TLS';
                throw FalhaTransportePncpException::tlsNaoConfirmado();
            }

            $categoriaFalha = 'RESPOSTA_INVALIDA';
            try {
                $resposta = new RespostaHttpPncp(
                    $resultado->getStatusHttp(),
                    $headersResposta,
                    $corpoResposta
                );
            } catch (InvalidArgumentException) {
                throw FalhaTransportePncpException::respostaHttpInvalida();
            }

            $categoriaFalha = match (true) {
                $resposta->getStatus() >= 500 => 'HTTP_5XX',
                $resposta->getStatus() >= 400 => 'HTTP_4XX',
                $resposta->getStatus() >= 300 => 'HTTP_3XX',
                default => null,
            };

            return $resposta;
        } finally {
            $this->registrarTentativa(
                $ordemHttp,
                $requisicao,
                $inicio,
                $resultado,
                $houveRespostaHttp,
                $cabecalhosRespostaBrutos,
                $corpoResposta,
                $categoriaFalha
            );
        }
    }

    private function registrarTentativa(
        int $ordemHttp,
        RequisicaoHttpPncp $requisicao,
        int $inicio,
        ?ResultadoExecucaoCurlPncp $resultado,
        bool $houveRespostaHttp,
        string $cabecalhosResposta,
        string $corpoResposta,
        ?string $categoriaFalha
    ): void {
        if ($this->logger === null) {
            return;
        }

        $metadata = [
            'ordemHttp' => $ordemHttp,
            'operacao' => $this->classificarOperacao($requisicao),
            'metodo' => $requisicao->getMetodo(),
            'ambientePncp' => $this->configuracao->getAmbiente()->value,
            'duracaoTentativaMs' => max(0, intdiv(hrtime(true) - $inicio, 1_000_000)),
        ];

        $corpoRequisicao = $requisicao->getCorpo();
        if ($corpoRequisicao !== null) {
            $metadata['requisicaoBytes'] = strlen($corpoRequisicao);
            $metadata['requisicaoSha256'] = hash('sha256', $corpoRequisicao);
        }

        $authorization = $requisicao->getHeaders()['authorization'] ?? null;
        $metadata['sessaoPresente'] = $authorization !== null;
        if (
            $authorization !== null
            && preg_match('/^Bearer[ \t]+([^\s]+)$/iD', $authorization, $partes) === 1
        ) {
            $metadata['sessaoFingerprintSha256'] = hash('sha256', $partes[1]);
        }

        if ($resultado !== null && $resultado->getErrno() !== 0) {
            $metadata['curl_errno'] = $resultado->getErrno();
        }

        if (
            $houveRespostaHttp
            && $resultado !== null
            && $resultado->getStatusHttp() >= 100
            && $resultado->getStatusHttp() <= 599
        ) {
            $metadata['status_http'] = $resultado->getStatusHttp();
            $metadata['retornoBytes'] = strlen($corpoResposta);
            $metadata['retornoSha256'] = hash('sha256', $corpoResposta);
        }

        if ($resultado !== null && $resultado->getVersaoHttp() !== null) {
            $metadata['httpVersion'] = $this->formatarVersaoHttp($resultado->getVersaoHttp());
        }

        if ($categoriaFalha !== null) {
            $metadata['categoriaFalha'] = $categoriaFalha;
        }

        if (
            $houveRespostaHttp
            && $resultado !== null
            && in_array($categoriaFalha, ['HTTP_3XX', 'HTTP_4XX', 'HTTP_5XX'], true)
            && $this->classificarOperacao($requisicao) !== 'AUTENTICAR'
        ) {
            $metadata['diagnosticoHttp'] = $this->montarDiagnosticoHttp(
                $requisicao,
                $resultado,
                $cabecalhosResposta,
                $corpoResposta
            );
        }

        try {
            $this->logger->log(
                $categoriaFalha === null ? 'info' : 'warning',
                'Tentativa HTTP PNCP encerrada.',
                [
                    'action' => AcaoTecnicaPncp::EXECUTAR_REQUISICAO_HTTP_PNCP->value,
                    'metadata' => $metadata,
                ]
            );
        } catch (Throwable) {
            // Observabilidade não modifica o resultado da requisição.
        }
    }

    private function validarRuntimeCurl(): void
    {
        if (!extension_loaded('curl') || !defined('CURLOPT_CUSTOMREQUEST')) {
            throw FalhaTransportePncpException::extensaoCurlIndisponivel();
        }
    }

    /**
     * @param array<string, string[]> $headersResposta
     * @return array<int, mixed>
     */
    private function montarOpcoes(
        RequisicaoHttpPncp $requisicao,
        array &$headersResposta,
        string &$corpoResposta,
        bool &$limiteRespostaExcedido,
        bool &$houveRespostaHttp,
        string &$cabecalhosRespostaBrutos
    ): array {
        $opcoes = [
            CURLOPT_CUSTOMREQUEST => $requisicao->getMetodo(),
            CURLOPT_HTTPHEADER => $this->formatarHeaders($requisicao->getHeaders()),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_CONNECTTIMEOUT => $this->configuracao->getTimeoutConexaoSegundos(),
            CURLOPT_TIMEOUT => $requisicao->getTimeoutSegundos(),
            CURLOPT_NOSIGNAL => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_HEADERFUNCTION => static function (
                mixed $handle,
                string $linha
            ) use (&$headersResposta, &$houveRespostaHttp, &$cabecalhosRespostaBrutos): int {
                $cabecalhosRespostaBrutos .= $linha;
                if (str_starts_with($linha, 'HTTP/')) {
                    $headersResposta = [];
                    $houveRespostaHttp = true;

                    return strlen($linha);
                }

                $separador = strpos($linha, ':');

                if ($separador !== false) {
                    $nome = strtolower(trim(substr($linha, 0, $separador)));
                    $valor = trim(substr($linha, $separador + 1));
                    $headersResposta[$nome][] = $valor;
                }

                return strlen($linha);
            },
            CURLOPT_WRITEFUNCTION => function (
                mixed $handle,
                string $trecho
            ) use (&$corpoResposta, &$limiteRespostaExcedido): int {
                if (
                    strlen($corpoResposta) + strlen($trecho)
                    > $this->configuracao->getLimiteRespostaBytes()
                ) {
                    $limiteRespostaExcedido = true;

                    return 0;
                }

                $corpoResposta .= $trecho;

                return strlen($trecho);
            },
        ];

        $caminhoCaBundle = $this->configuracao->getCaminhoCaBundle();

        if ($caminhoCaBundle !== null) {
            $opcoes[CURLOPT_CAINFO] = $caminhoCaBundle;
        }

        if ($requisicao->getCorpo() !== null) {
            $opcoes[CURLOPT_POSTFIELDS] = $requisicao->getCorpo();
        }

        return $opcoes;
    }

    /**
     * @param array<string, string> $headers
     * @return string[]
     */
    private function formatarHeaders(array $headers): array
    {
        $formatados = [];

        foreach ($headers as $nome => $valor) {
            $formatados[] = "{$nome}: {$valor}";
        }

        return $formatados;
    }

    private function classificarOperacao(RequisicaoHttpPncp $requisicao): string
    {
        $metodo = $requisicao->getMetodo();
        $caminho = $requisicao->getCaminho();

        if ($metodo === 'POST' && $caminho === '/v1/usuarios/login') {
            return 'AUTENTICAR';
        }
        if (
            $metodo === 'GET'
            && preg_match('#^/v1/orgaos/[^/]+/pca/[^/]+/consolidado/unidades(?:\?.*)?$#D', $caminho) === 1
        ) {
            return 'CONSULTAR_UNIDADES_PCA';
        }
        if (
            $metodo === 'GET'
            && preg_match('#^/v1/orgaos/[^/]+/pca/[^/]+(?:/[^/]+)?/consolidado(?:\?.*)?$#D', $caminho) === 1
        ) {
            return 'CONSULTAR_CONSOLIDADO_PCA';
        }
        if (
            $metodo === 'GET'
            && preg_match('#^/v1/orgaos/[^/]+/unidades(?:\?.*)?$#D', $caminho) === 1
        ) {
            return 'CONSULTAR_UNIDADES_ORGAO';
        }
        if (preg_match('#^/v1/orgaos/[^/]+/pca/[^/]+/[^/]+/itens(?:\?.*)?$#D', $caminho) === 1) {
            return match ($metodo) {
                'GET' => 'CONSULTAR_ITENS_PCA',
                'POST' => 'INCLUIR_ITENS_PCA',
                'DELETE' => 'EXCLUIR_ITENS_PCA',
                default => 'REQUISICAO_PNCP',
            };
        }
        if ($metodo === 'POST' && preg_match('#^/v1/orgaos/[^/]+/pca$#D', $caminho) === 1) {
            return 'PUBLICAR_PCA';
        }
        if ($metodo === 'DELETE' && preg_match('#^/v1/orgaos/[^/]+/pca/[^/]+/[^/]+$#D', $caminho) === 1) {
            return 'EXCLUIR_PCA';
        }

        return $metodo === 'GET' ? 'CONSULTAR_PNCP' : 'REQUISICAO_PNCP';
    }

    private function formatarVersaoHttp(int $versao): string
    {
        $versoes = [];
        foreach ([
            'CURL_HTTP_VERSION_1_0' => '1.0',
            'CURL_HTTP_VERSION_1_1' => '1.1',
            'CURL_HTTP_VERSION_2_0' => '2',
            'CURL_HTTP_VERSION_2TLS' => '2',
            'CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE' => '2',
            'CURL_HTTP_VERSION_3' => '3',
        ] as $constante => $rotulo) {
            if (defined($constante)) {
                $versoes[(int) constant($constante)] = $rotulo;
            }
        }

        return $versoes[$versao] ?? 'DESCONHECIDA_' . $versao;
    }

    /** @return array<string, mixed> */
    private function montarDiagnosticoHttp(
        RequisicaoHttpPncp $requisicao,
        ResultadoExecucaoCurlPncp $resultado,
        string $cabecalhosResposta,
        string $corpoResposta
    ): array {
        $corpoRequisicao = $requisicao->getCorpo() ?? '';
        $cabecalhosEnviados = $resultado->getCabecalhosEnviados();
        if ($cabecalhosEnviados === null) {
            $cabecalhosEnviados = implode("\r\n", $this->formatarHeaders(
                $requisicao->getHeaders()
            ));
        }

        return [
            'url' => $this->configuracao->getBaseUrl() . $requisicao->getCaminho(),
            'request' => array_merge(
                [
                    'method' => $requisicao->getMetodo(),
                    'headersSent' => $this->redigirCabecalhos($cabecalhosEnviados),
                    'authorizationDiagnostic' => $this->diagnosticarAutorizacao(
                        $requisicao->getHeaders()['authorization'] ?? null
                    ),
                ],
                $this->descreverCorpo($corpoRequisicao)
            ),
            'response' => array_merge(
                [
                    'status' => $resultado->getStatusHttp(),
                    'headersReceived' => $this->redigirCabecalhos($cabecalhosResposta),
                ],
                $this->descreverCorpo($corpoResposta)
            ),
            'connection' => $resultado->getDiagnosticoConexao(),
            'curlCommandTemplate' => $this->montarCurlSeguro(
                $requisicao,
                $this->configuracao->getBaseUrl() . $requisicao->getCaminho()
            ),
        ];
    }

    private function redigirCabecalhos(string $cabecalhos): string
    {
        return (string) preg_replace(
            '/^(authorization|proxy-authorization|cookie|set-cookie):.*$/im',
            '$1: [REDACTED]',
            $cabecalhos
        );
    }

    /** @return array<string, mixed> */
    private function diagnosticarAutorizacao(?string $authorization): array
    {
        if (
            $authorization === null
            || preg_match('/^Bearer[ \t]+([^\s]+)$/iD', $authorization, $partes) !== 1
        ) {
            return ['present' => $authorization !== null, 'scheme' => null];
        }

        $token = $partes[1];
        $segmentos = explode('.', $token);
        $diagnostico = [
            'present' => true,
            'scheme' => 'Bearer',
            'tokenSha256' => hash('sha256', $token),
            'format' => count($segmentos) === 3 ? 'JWT' : 'OPAQUE',
        ];

        if (count($segmentos) === 3) {
            $diagnostico['jwtHeader'] = $this->decodificarSegmentoJwt($segmentos[0]);
            $diagnostico['jwtClaims'] = $this->decodificarSegmentoJwt($segmentos[1]);
        }

        return $diagnostico;
    }

    /** @return array<string, mixed>|null */
    private function decodificarSegmentoJwt(string $segmento): ?array
    {
        $preenchimento = (4 - strlen($segmento) % 4) % 4;
        $json = base64_decode(
            strtr($segmento, '-_', '+/') . str_repeat('=', $preenchimento),
            true
        );
        if ($json === false) {
            return null;
        }

        $decodificado = json_decode($json, true);

        return is_array($decodificado) && !array_is_list($decodificado)
            ? $decodificado
            : null;
    }

    /** @return array<string, int|string|null> */
    private function descreverCorpo(string $corpo): array
    {
        $utf8Valido = preg_match('//u', $corpo) === 1;

        return [
            'bodyUtf8' => $utf8Valido ? $corpo : null,
            'bodyBase64' => $utf8Valido ? null : base64_encode($corpo),
            'bodyBytes' => strlen($corpo),
            'bodySha256' => hash('sha256', $corpo),
        ];
    }

    private function montarCurlSeguro(RequisicaoHttpPncp $requisicao, string $url): string
    {
        $partes = [
            "curl --request '" . $requisicao->getMetodo() . "' '" . $url . "'",
        ];

        foreach ($requisicao->getHeaders() as $nome => $valor) {
            if ($nome === 'authorization') {
                $partes[] = '  --header "authorization: Bearer ${PNCP_BEARER_TOKEN}"';
                continue;
            }
            if ($nome === 'cookie') {
                continue;
            }
            $partes[] = "  --header '" . $nome . ': ' . $valor . "'";
        }

        if ($requisicao->getCorpo() !== null) {
            $partes[] = "  --data-binary '@payload.json'";
        }

        return 'PNCP_BEARER_TOKEN=[PREENCHER_NO_TERMINAL_PROTEGIDO]'
            . PHP_EOL
            . implode(' ' . chr(92) . PHP_EOL, $partes);
    }
}
