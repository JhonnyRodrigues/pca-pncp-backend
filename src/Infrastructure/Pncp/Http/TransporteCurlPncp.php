<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

use InvalidArgumentException;

final class TransporteCurlPncp implements TransporteHttpPncp
{
    public function __construct(
        private readonly ConfiguracaoTransportePncp $configuracao,
        private readonly ExecutorCurlPncp $executor
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

        $opcoes = $this->montarOpcoes(
            $requisicao,
            $headersResposta,
            $corpoResposta,
            $limiteRespostaExcedido
        );
        $resultado = $this->executor->executar(
            $this->configuracao->getBaseUrl() . $requisicao->getCaminho(),
            $opcoes
        );

        if ($limiteRespostaExcedido) {
            throw FalhaTransportePncpException::limiteRespostaExcedido();
        }

        if (!$resultado->foiExecutado() || $resultado->getErrno() !== 0) {
            throw FalhaTransportePncpException::execucaoCurlFalhou($resultado->getErrno());
        }

        if ($resultado->getResultadoVerificacaoTls() !== 0) {
            throw FalhaTransportePncpException::tlsNaoConfirmado();
        }

        try {
            return new RespostaHttpPncp(
                $resultado->getStatusHttp(),
                $headersResposta,
                $corpoResposta
            );
        } catch (InvalidArgumentException) {
            throw FalhaTransportePncpException::respostaHttpInvalida();
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
        bool &$limiteRespostaExcedido
    ): array {
        $opcoes = [
            CURLOPT_CUSTOMREQUEST => $requisicao->getMetodo(),
            CURLOPT_HTTPHEADER => $this->formatarHeaders($requisicao->getHeaders()),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
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
            ) use (&$headersResposta): int {
                if (str_starts_with($linha, 'HTTP/')) {
                    $headersResposta = [];

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
}
