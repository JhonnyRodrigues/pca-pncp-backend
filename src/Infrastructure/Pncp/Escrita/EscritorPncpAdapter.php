<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Escrita;

use Psr\Log\LoggerInterface;
use Semae\Pca\Application\Commands\ComandoExcluirItensPca;
use Semae\Pca\Application\Commands\ComandoExcluirPca;
use Semae\Pca\Application\Commands\ComandoInserirItensPca;
use Semae\Pca\Application\Commands\ComandoPublicarPca;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Enums\SituacaoEscritaPncp;
use Semae\Pca\Application\Ports\EscritorPncpPort;
use Semae\Pca\Application\Results\ResultadoEscritaPncp;
use Semae\Pca\Application\Results\ResultadoPublicacaoPncp;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Observabilidade\AcaoTecnicaPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\SerializadorPayloadPncp;
use Throwable;

final class EscritorPncpAdapter implements EscritorPncpPort
{
    private const STATUS_PUBLICACAO_ACEITOS = [200, 201];
    private const PREFIXO_LOCATION_ALTERNATIVO_HOMOLOGACAO = 'https://treina.pncp.gov.br/pncp-api';
    private const PREFIXO_LOCATION_ALTERNATIVO_PRODUCAO = 'https://pncp.gov.br/pncp-api';

    /** @var string[] */
    private const FALHAS_TRANSPORTE_SISTEMICAS = [
        'PNCP_TRANSPORTE_CURL_INDISPONIVEL',
        'PNCP_TRANSPORTE_INICIALIZACAO_FALHOU',
        'PNCP_TRANSPORTE_CONFIGURACAO_FALHOU',
        'PNCP_TRANSPORTE_PAYLOAD_EXCEDE_LIMITE',
        'PNCP_TRANSPORTE_TLS_NAO_CONFIRMADO',
    ];

    public function __construct(
        private readonly TransporteHttpPncp $transporte,
        private readonly ConfiguracaoTransportePncp $configuracao,
        private readonly SerializadorPayloadPncp $serializador,
        private readonly LoggerInterface $logger
    ) {}

    public function publicar(
        SessaoPncp $sessao,
        ComandoPublicarPca $comando
    ): ResultadoPublicacaoPncp {
        if (!$sessao instanceof SessaoPncpAutenticada) {
            return $this->falhaSistemicaPublicacao(
                'PNCP_PUBLICACAO_SESSAO_INVALIDA',
                'A sessão PNCP não é compatível com a publicação.',
                count($comando->getDemandas())
            );
        }

        try {
            $requisicao = new RequisicaoHttpPncp(
                'POST',
                '/v1/orgaos/' . rawurlencode($comando->getCnpj()->getValor()) . '/pca',
                [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $sessao->getTokenOpaco(),
                ],
                $this->serializador->serializarPublicacao($comando),
                $this->configuracao->getTimeoutEscritaSegundos()
            );
        } catch (Throwable) {
            return $this->falhaSistemicaPublicacao(
                'PNCP_PUBLICACAO_PREPARACAO_FALHOU',
                'Não foi possível preparar a publicação no PNCP.',
                count($comando->getDemandas())
            );
        }

        try {
            $resposta = $this->transporte->enviar($requisicao);
        } catch (FalhaTransportePncpException $falha) {
            if (in_array($falha->getCodigoSeguro(), self::FALHAS_TRANSPORTE_SISTEMICAS, true)) {
                return $this->falhaSistemicaPublicacao(
                    'PNCP_PUBLICACAO_TRANSPORTE_FALHOU',
                    'Não foi possível executar a publicação no PNCP.',
                    count($comando->getDemandas())
                );
            }

            return $this->resultadoIncertoPublicacao(count($comando->getDemandas()));
        } catch (Throwable) {
            return $this->resultadoIncertoPublicacao(count($comando->getDemandas()));
        }

        return $this->classificarPublicacao($resposta, $comando);
    }

    public function inserirItens(
        SessaoPncp $sessao,
        ComandoInserirItensPca $comando
    ): ResultadoEscritaPncp {
        $acao = AcaoTecnicaPncp::INSERIR_ITENS_PCA_PNCP;

        if (!$sessao instanceof SessaoPncpAutenticada) {
            return $this->falhaSistemicaEscrita(
                $acao,
                'PNCP_INCLUSAO_ITENS_SESSAO_INVALIDA',
                'A sessão PNCP não é compatível com a inclusão de itens.',
                count($comando->getDemandas())
            );
        }

        try {
            $requisicao = $this->criarRequisicaoItens(
                'POST',
                $sessao,
                $comando->getCnpj()->getValor(),
                $comando->getUnidadeExterna()->getAnoExecucao()->getValor(),
                $comando->getSequencialPca()->getValor(),
                $this->serializador->serializarInclusaoItens($comando)
            );
        } catch (Throwable) {
            return $this->falhaSistemicaEscrita(
                $acao,
                'PNCP_INCLUSAO_ITENS_PREPARACAO_FALHOU',
                'Não foi possível preparar a inclusão de itens no PNCP.',
                count($comando->getDemandas())
            );
        }

        return $this->enviarEscrita(
            $requisicao,
            [200, 201],
            $acao,
            count($comando->getDemandas()),
            'INCLUSAO_ITENS',
            'inclusão de itens'
        );
    }

    public function excluirItens(
        SessaoPncp $sessao,
        ComandoExcluirItensPca $comando
    ): ResultadoEscritaPncp {
        $acao = AcaoTecnicaPncp::EXCLUIR_ITENS_PCA_PNCP;

        if (!$sessao instanceof SessaoPncpAutenticada) {
            return $this->falhaSistemicaEscrita(
                $acao,
                'PNCP_EXCLUSAO_ITENS_SESSAO_INVALIDA',
                'A sessão PNCP não é compatível com a exclusão de itens.',
                count($comando->getIdsDemandas())
            );
        }

        try {
            $requisicao = $this->criarRequisicaoItens(
                'DELETE',
                $sessao,
                $comando->getCnpj()->getValor(),
                $comando->getUnidadeExterna()->getAnoExecucao()->getValor(),
                $comando->getSequencialPca()->getValor(),
                $this->serializador->serializarExclusaoItens($comando)
            );
        } catch (Throwable) {
            return $this->falhaSistemicaEscrita(
                $acao,
                'PNCP_EXCLUSAO_ITENS_PREPARACAO_FALHOU',
                'Não foi possível preparar a exclusão de itens no PNCP.',
                count($comando->getIdsDemandas())
            );
        }

        return $this->enviarEscrita(
            $requisicao,
            [200],
            $acao,
            count($comando->getIdsDemandas()),
            'EXCLUSAO_ITENS',
            'exclusão de itens'
        );
    }

    public function excluirPca(
        SessaoPncp $sessao,
        ComandoExcluirPca $comando
    ): ResultadoEscritaPncp {
        $acao = AcaoTecnicaPncp::EXCLUIR_PCA_PNCP;

        if (!$sessao instanceof SessaoPncpAutenticada) {
            return $this->falhaSistemicaEscrita(
                $acao,
                'PNCP_EXCLUSAO_PCA_SESSAO_INVALIDA',
                'A sessão PNCP não é compatível com a exclusão integral.',
                1
            );
        }

        try {
            $requisicao = new RequisicaoHttpPncp(
                'DELETE',
                '/v1/orgaos/' . rawurlencode($comando->getCnpj()->getValor())
                    . '/pca/' . $comando->getUnidadeExterna()->getAnoExecucao()->getValor()
                    . '/' . $comando->getSequencialPca()->getValor(),
                [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $sessao->getTokenOpaco(),
                ],
                $this->serializador->serializarExclusaoPca($comando),
                $this->configuracao->getTimeoutEscritaSegundos()
            );
        } catch (Throwable) {
            return $this->falhaSistemicaEscrita(
                $acao,
                'PNCP_EXCLUSAO_PCA_PREPARACAO_FALHOU',
                'Não foi possível preparar a exclusão integral no PNCP.',
                1
            );
        }

        return $this->enviarEscrita(
            $requisicao,
            [200],
            $acao,
            1,
            'EXCLUSAO_PCA',
            'exclusão integral'
        );
    }

    private function classificarPublicacao(
        RespostaHttpPncp $resposta,
        ComandoPublicarPca $comando
    ): ResultadoPublicacaoPncp {
        $status = $resposta->getStatus();
        $quantidade = count($comando->getDemandas());

        if (in_array($status, [400, 422], true)) {
            return $this->rejeitadaPublicacao($resposta, $quantidade);
        }

        if (in_array($status, [401, 403], true)) {
            return $this->falhaSistemicaPublicacao(
                'PNCP_PUBLICACAO_NAO_AUTORIZADA',
                $this->acrescentarDetalhePncp(
                    'A publicação no PNCP não foi autorizada.',
                    $resposta
                ),
                $quantidade,
                $status
            );
        }

        if (!in_array($status, self::STATUS_PUBLICACAO_ACEITOS, true)) {
            return $this->resultadoIncertoPublicacao(
                $quantidade,
                $status,
                resposta: $resposta
            );
        }

        $sequencial = $this->extrairSequencial($resposta, $comando);

        if ($sequencial === null) {
            return $this->resultadoIncertoPublicacao(
                $quantidade,
                $status,
                $this->diagnosticarLocation($resposta)
            );
        }

        $this->logger->info('Publicação no PNCP aceita.', [
            'action' => AcaoTecnicaPncp::PUBLICAR_PCA_PNCP->value,
            'metadata' => [
                'situacao' => SituacaoEscritaPncp::ACEITA->value,
                'statusHttp' => $status,
                'quantidade' => $quantidade,
            ],
        ]);

        return ResultadoPublicacaoPncp::aceita($sequencial);
    }

    private function criarRequisicaoItens(
        string $metodo,
        SessaoPncpAutenticada $sessao,
        string $cnpj,
        int $ano,
        int $sequencial,
        string $corpo
    ): RequisicaoHttpPncp {
        return new RequisicaoHttpPncp(
            $metodo,
            '/v1/orgaos/' . rawurlencode($cnpj) . '/pca/' . $ano . '/' . $sequencial . '/itens',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $sessao->getTokenOpaco(),
            ],
            $corpo,
            $this->configuracao->getTimeoutEscritaSegundos()
        );
    }

    /** @param int[] $statusAceitos */
    private function enviarEscrita(
        RequisicaoHttpPncp $requisicao,
        array $statusAceitos,
        AcaoTecnicaPncp $acao,
        int $quantidade,
        string $codigoOperacao,
        string $descricaoOperacao
    ): ResultadoEscritaPncp {
        try {
            $resposta = $this->transporte->enviar($requisicao);
        } catch (FalhaTransportePncpException $falha) {
            if (in_array($falha->getCodigoSeguro(), self::FALHAS_TRANSPORTE_SISTEMICAS, true)) {
                return $this->falhaSistemicaEscrita(
                    $acao,
                    'PNCP_' . $codigoOperacao . '_TRANSPORTE_FALHOU',
                    'Não foi possível executar a ' . $descricaoOperacao . ' no PNCP.',
                    $quantidade
                );
            }

            return $this->resultadoIncertoEscrita(
                $acao,
                $codigoOperacao,
                $descricaoOperacao,
                $quantidade
            );
        } catch (Throwable) {
            return $this->resultadoIncertoEscrita(
                $acao,
                $codigoOperacao,
                $descricaoOperacao,
                $quantidade
            );
        }

        $status = $resposta->getStatus();

        if (in_array($status, $statusAceitos, true)) {
            $this->logger->info(ucfirst($descricaoOperacao) . ' no PNCP aceita.', [
                'action' => $acao->value,
                'metadata' => [
                    'situacao' => SituacaoEscritaPncp::ACEITA->value,
                    'statusHttp' => $status,
                    'quantidade' => $quantidade,
                ],
            ]);

            return ResultadoEscritaPncp::aceita();
        }

        if (in_array($status, [400, 422], true)) {
            return $this->rejeitadaEscrita(
                $acao,
                'PNCP_' . $codigoOperacao . '_REJEITADA',
                $this->acrescentarDetalhePncp(
                    'O PNCP rejeitou a ' . $descricaoOperacao . '.',
                    $resposta
                ),
                $status,
                $quantidade
            );
        }

        if (in_array($status, [401, 403], true)) {
            return $this->falhaSistemicaEscrita(
                $acao,
                'PNCP_' . $codigoOperacao . '_NAO_AUTORIZADA',
                $this->acrescentarDetalhePncp(
                    'A ' . $descricaoOperacao . ' no PNCP não foi autorizada.',
                    $resposta
                ),
                $quantidade,
                $status
            );
        }

        return $this->resultadoIncertoEscrita(
            $acao,
            $codigoOperacao,
            $descricaoOperacao,
            $quantidade,
            $status,
            $resposta
        );
    }

    private function rejeitadaEscrita(
        AcaoTecnicaPncp $acao,
        string $codigo,
        string $mensagem,
        int $status,
        int $quantidade
    ): ResultadoEscritaPncp {
        $falha = new FalhaSegura($codigo, $mensagem);
        $this->registrarFalha(
            $acao,
            $falha,
            SituacaoEscritaPncp::REJEITADA,
            $quantidade,
            $status
        );

        return ResultadoEscritaPncp::rejeitada($falha);
    }

    private function resultadoIncertoEscrita(
        AcaoTecnicaPncp $acao,
        string $codigoOperacao,
        string $descricaoOperacao,
        int $quantidade,
        ?int $status = null,
        ?RespostaHttpPncp $resposta = null
    ): ResultadoEscritaPncp {
        $falha = new FalhaSegura(
            'PNCP_' . $codigoOperacao . '_RESULTADO_INCERTO',
            $this->acrescentarDetalhePncp(
                'Não foi possível determinar o resultado da '
                    . $descricaoOperacao . ' no PNCP.',
                $resposta
            )
        );
        $this->registrarFalha(
            $acao,
            $falha,
            SituacaoEscritaPncp::RESULTADO_INCERTO,
            $quantidade,
            $status
        );

        return ResultadoEscritaPncp::resultadoIncerto($falha);
    }

    private function falhaSistemicaEscrita(
        AcaoTecnicaPncp $acao,
        string $codigo,
        string $mensagem,
        int $quantidade,
        ?int $status = null
    ): ResultadoEscritaPncp {
        $falha = new FalhaSegura($codigo, $mensagem);
        $this->registrarFalha(
            $acao,
            $falha,
            SituacaoEscritaPncp::FALHA_SISTEMICA,
            $quantidade,
            $status
        );

        return ResultadoEscritaPncp::falhaSistemica($falha);
    }

    private function extrairSequencial(
        RespostaHttpPncp $resposta,
        ComandoPublicarPca $comando
    ): ?SequencialPca {
        $locations = $resposta->getHeaders()['location'] ?? [];

        if (count($locations) !== 1) {
            return null;
        }

        $location = trim($locations[0]);
        $cnpj = rawurlencode($comando->getCnpj()->getValor());
        $ano = $comando->getUnidadeExterna()->getAnoExecucao()->getValor();
        $recurso = '/v1/orgaos/' . $cnpj . '/pca/' . $ano . '/';
        $prefixos = [
            $recurso,
            rtrim($this->configuracao->getBaseUrl(), '/') . $recurso,
        ];
        if ($this->configuracao->getAmbiente() === AmbientePncp::HOMOLOGACAO) {
            $prefixos[] = self::PREFIXO_LOCATION_ALTERNATIVO_HOMOLOGACAO . $recurso;
        }
        if ($this->configuracao->getAmbiente() === AmbientePncp::PRODUCAO) {
            $prefixos[] = self::PREFIXO_LOCATION_ALTERNATIVO_PRODUCAO . $recurso;
        }

        foreach ($prefixos as $prefixo) {
            $correspondencias = [];

            if (
                preg_match(
                    '/^' . preg_quote($prefixo, '/') . '([1-9][0-9]*)$/D',
                    $location,
                    $correspondencias
                ) !== 1
            ) {
                continue;
            }

            $valor = filter_var(
                $correspondencias[1],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            return is_int($valor) ? new SequencialPca($valor) : null;
        }

        return null;
    }

    /** @return array{locationCategoria: string, quantidadeLocations: int} */
    private function diagnosticarLocation(RespostaHttpPncp $resposta): array
    {
        $locations = $resposta->getHeaders()['location'] ?? [];
        $quantidade = count($locations);

        if ($quantidade === 0) {
            $categoria = 'AUSENTE';
        } elseif ($quantidade !== 1) {
            $categoria = 'MULTIPLO';
        } else {
            $location = trim($locations[0]);

            if ($location === '') {
                $categoria = 'VAZIO';
            } elseif (str_starts_with($location, '/')) {
                $categoria = 'RELATIVO_FORA_DO_CONTRATO';
            } elseif (parse_url($location, PHP_URL_HOST) === 'pncp.gov.br') {
                $categoria = 'ABSOLUTO_HOST_OFICIAL_FORA_DO_CONTRATO';
            } else {
                $categoria = 'ABSOLUTO_FORA_DO_CONTRATO';
            }
        }

        return [
            'locationCategoria' => $categoria,
            'quantidadeLocations' => $quantidade,
        ];
    }

    private function rejeitadaPublicacao(
        RespostaHttpPncp $resposta,
        int $quantidade
    ): ResultadoPublicacaoPncp {
        $falha = new FalhaSegura(
            'PNCP_PUBLICACAO_REJEITADA',
            $this->acrescentarDetalhePncp(
                'O PNCP rejeitou a publicação.',
                $resposta
            )
        );
        $this->registrarFalha(
            AcaoTecnicaPncp::PUBLICAR_PCA_PNCP,
            $falha,
            SituacaoEscritaPncp::REJEITADA,
            $quantidade,
            $resposta->getStatus()
        );

        return ResultadoPublicacaoPncp::rejeitada($falha);
    }

    private function resultadoIncertoPublicacao(
        int $quantidade,
        ?int $status = null,
        array $diagnosticoLocation = [],
        ?RespostaHttpPncp $resposta = null
    ): ResultadoPublicacaoPncp
    {
        $falha = new FalhaSegura(
            'PNCP_PUBLICACAO_RESULTADO_INCERTO',
            $this->acrescentarDetalhePncp(
                'Não foi possível determinar o resultado da publicação no PNCP.',
                $resposta
            )
        );
        $this->registrarFalha(
            AcaoTecnicaPncp::PUBLICAR_PCA_PNCP,
            $falha,
            SituacaoEscritaPncp::RESULTADO_INCERTO,
            $quantidade,
            $status,
            $diagnosticoLocation
        );

        return ResultadoPublicacaoPncp::resultadoIncerto($falha);
    }

    private function falhaSistemicaPublicacao(
        string $codigo,
        string $mensagem,
        int $quantidade,
        ?int $status = null
    ): ResultadoPublicacaoPncp {
        $falha = new FalhaSegura($codigo, $mensagem);
        $this->registrarFalha(
            AcaoTecnicaPncp::PUBLICAR_PCA_PNCP,
            $falha,
            SituacaoEscritaPncp::FALHA_SISTEMICA,
            $quantidade,
            $status
        );

        return ResultadoPublicacaoPncp::falhaSistemica($falha);
    }

    private function acrescentarDetalhePncp(
        string $mensagem,
        ?RespostaHttpPncp $resposta
    ): string {
        if ($resposta === null) {
            return $mensagem;
        }

        $corpo = trim($resposta->getCorpo());
        if ($corpo === '') {
            return $mensagem;
        }

        $decodificado = json_decode($corpo, true);
        if (!is_array($decodificado)) {
            return $mensagem;
        }

        $detalhe = $decodificado['message'] ?? $decodificado['mensagem'] ?? null;
        if (!is_string($detalhe) || trim($detalhe) === '') {
            return $mensagem;
        }

        return $mensagem . ' O PNCP informou: ' . trim($detalhe);
    }

    private function registrarFalha(
        AcaoTecnicaPncp $acao,
        FalhaSegura $falha,
        SituacaoEscritaPncp $situacao,
        int $quantidade,
        ?int $status,
        array $metadataAdicional = []
    ): void {
        $metadata = ['situacao' => $situacao->value];

        if ($status !== null) {
            $metadata['statusHttp'] = $status;
        }

        $metadata['quantidade'] = $quantidade;
        $metadata = array_merge($metadata, $metadataAdicional);

        $this->logger->error($falha->getMensagem(), [
            'action' => $acao->value,
            'error' => [
                'code' => $falha->getCodigo(),
                'message' => $falha->getMensagem(),
            ],
            'metadata' => $metadata,
        ]);
    }
}
