<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Leitura;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Semae\Pca\Application\Contracts\SessaoPncp;
use Semae\Pca\Application\Ports\LeitorPncpPort;
use Semae\Pca\Application\Results\ResultadoBuscaCandidatosPca;
use Semae\Pca\Application\Results\ResultadoObservacaoPca;
use Semae\Pca\Application\ValueObjects\FalhaSegura;
use Semae\Pca\Application\ValueObjects\UnidadeExternaPublicacao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Domain\ValueObjects\PcaObservado;
use Semae\Pca\Domain\ValueObjects\SequencialPca;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\SessaoPncpAutenticada;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\FalhaTransportePncpException;
use Semae\Pca\Infrastructure\Pncp\Http\RequisicaoHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\RespostaHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteHttpPncp;
use Semae\Pca\Infrastructure\Pncp\Observabilidade\AcaoTecnicaPncp;
use Throwable;
use UnexpectedValueException;

final class LeitorPncpAdapter implements LeitorPncpPort
{
    private const NUMERO_ITEM_MAXIMO = 2_147_483_647;
    private const QUANTIDADE_MAXIMA_ITENS = 1_000;
    private const STATUS_HTTP_TRANSITORIOS = [408, 429, 502, 503, 504];
    private const CURLE_OPERATION_TIMEDOUT = 28;

    public function __construct(
        private readonly TransporteHttpPncp $transporte,
        private readonly ConfiguracaoTransportePncp $configuracaoTransporte,
        private readonly ConfiguracaoLeituraPncp $configuracaoLeitura,
        private readonly LoggerInterface $logger,
        private readonly TemporizadorPncp $temporizador
    ) {}

    public function observarPcaConhecido(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade,
        SequencialPca $sequencialConhecido
    ): ResultadoObservacaoPca {
        if (!$sessao instanceof SessaoPncpAutenticada) {
            return $this->falhaSistemicaObservacao(
                'PNCP_LEITURA_SESSAO_INVALIDA',
                'A sessão PNCP não é compatível com a leitura.'
            );
        }

        $ano = $unidade->getAnoExecucao()->getValor();
        $uasg = $unidade->getUasgExterna()->getCodigo();
        $sequencial = $sequencialConhecido->getValor();
        $baseAno = '/v1/orgaos/' . rawurlencode($cnpj->getValor()) . '/pca/' . $ano;
        $base = $baseAno . '/' . $sequencial;

        try {
            $consolidado = $this->get(
                $sessao,
                $base . '/consolidado',
                AcaoTecnicaPncp::OBSERVAR_PCA_PNCP
            );

            if ($consolidado->getStatus() === 204) {
                $candidatos = $this->lerTodosOsCandidatos(
                    $sessao,
                    $baseAno . '/consolidado/unidades',
                    $cnpj,
                    $ano,
                    AcaoTecnicaPncp::OBSERVAR_PCA_PNCP
                );

                foreach ($candidatos as $candidato) {
                    if ($candidato['uasg'] === $uasg) {
                        throw new DomainException();
                    }
                }

                return $this->ausenciaDefinitivaObservacao();
            }

            $this->exigirStatusOk($consolidado);
            $identidade = $this->decodificarObjeto($consolidado);
            $this->validarIdentidade($identidade, $cnpj, $ano, $uasg, $sequencial);

            $ids = $this->lerTodosOsIds(
                $sessao,
                $base . '/itens',
                $cnpj,
                $ano,
                $uasg,
                $sequencial,
                AcaoTecnicaPncp::OBSERVAR_PCA_PNCP
            );

            return $this->observacaoEncontrada($sequencialConhecido, $ids);
        } catch (DomainException) {
            return $this->conflitoObservacao(
                'PNCP_IDENTIDADE_DIVERGENTE',
                'A identidade observada no PNCP diverge da consulta.'
            );
        } catch (JsonException | UnexpectedValueException) {
            return $this->inconclusivaObservacao(
                'PNCP_RESPOSTA_INCONCLUSIVA',
                'A resposta de leitura do PNCP não permitiu uma conclusão segura.'
            );
        } catch (FalhaTransportePncpException) {
            return $this->falhaSistemicaObservacao(
                'PNCP_LEITURA_TRANSPORTE_FALHOU',
                'Não foi possível consultar o PNCP.'
            );
        } catch (Throwable) {
            return $this->falhaSistemicaObservacao(
                'PNCP_LEITURA_FALHOU',
                'Não foi possível concluir a leitura do PNCP.'
            );
        }
    }

    public function localizarCandidatos(
        SessaoPncp $sessao,
        Cnpj $cnpj,
        UnidadeExternaPublicacao $unidade
    ): ResultadoBuscaCandidatosPca {
        if (!$sessao instanceof SessaoPncpAutenticada) {
            return $this->falhaSistemicaBusca(
                'PNCP_LEITURA_SESSAO_INVALIDA',
                'A sessão PNCP não é compatível com a busca de candidatos.'
            );
        }

        $ano = $unidade->getAnoExecucao()->getValor();
        $uasgEsperada = $unidade->getUasgExterna()->getCodigo();
        $base = '/v1/orgaos/' . rawurlencode($cnpj->getValor()) . '/pca/' . $ano;

        try {
            $candidatos = $this->lerTodosOsCandidatos(
                $sessao,
                $base . '/consolidado/unidades',
                $cnpj,
                $ano,
                AcaoTecnicaPncp::LOCALIZAR_CANDIDATOS_PNCP
            );
            $correspondentes = array_values(array_filter(
                $candidatos,
                static fn (array $candidato): bool => $candidato['uasg'] === $uasgEsperada
            ));

            if ($correspondentes === []) {
                return $this->buscaConcluidaSemCandidatos();
            }

            if (count($correspondentes) > 1) {
                throw new DomainException();
            }

            $sequencial = $correspondentes[0]['sequencial'];
            $ids = $this->lerTodosOsIds(
                $sessao,
                $base . '/' . $sequencial . '/itens',
                $cnpj,
                $ano,
                $uasgEsperada,
                $sequencial,
                AcaoTecnicaPncp::LOCALIZAR_CANDIDATOS_PNCP
            );

            return $this->buscaConcluida([
                PcaObservado::ativo(new SequencialPca($sequencial), $ids),
            ]);
        } catch (DomainException) {
            return $this->conflitoBusca(
                'PNCP_IDENTIDADE_DIVERGENTE',
                'A identidade de candidato observada no PNCP diverge da consulta.'
            );
        } catch (JsonException | UnexpectedValueException) {
            return $this->inconclusivaBusca(
                'PNCP_RESPOSTA_INCONCLUSIVA',
                'A resposta da busca no PNCP não permitiu uma conclusão segura.'
            );
        } catch (FalhaTransportePncpException) {
            return $this->falhaSistemicaBusca(
                'PNCP_LEITURA_TRANSPORTE_FALHOU',
                'Não foi possível consultar candidatos no PNCP.'
            );
        } catch (Throwable) {
            return $this->falhaSistemicaBusca(
                'PNCP_LEITURA_FALHOU',
                'Não foi possível concluir a busca de candidatos no PNCP.'
            );
        }
    }

    private function get(
        SessaoPncpAutenticada $sessao,
        string $caminho,
        AcaoTecnicaPncp $acao,
        ?int $pagina = null
    ): RespostaHttpPncp {
        $requisicao = new RequisicaoHttpPncp(
            'GET',
            $caminho,
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $sessao->getTokenOpaco(),
            ],
            null,
            $this->configuracaoTransporte->getTimeoutLeituraSegundos()
        );

        for ($tentativa = 1; $tentativa <= 2; $tentativa++) {
            try {
                $resposta = $this->transporte->enviar($requisicao);
                $this->registrarTentativa($acao, $tentativa, $pagina, $resposta->getStatus());

                if (!in_array($resposta->getStatus(), self::STATUS_HTTP_TRANSITORIOS, true)) {
                    return $resposta;
                }

                if ($tentativa === 2) {
                    throw new UnexpectedValueException();
                }

                $this->temporizador->aguardarMilissegundos(
                    $this->calcularEsperaRetryAfter($resposta)
                );
            } catch (FalhaTransportePncpException $falha) {
                $this->registrarTentativa(
                    $acao,
                    $tentativa,
                    $pagina,
                    null,
                    $falha->getCodigoSeguro()
                );

                if ($falha->getErrnoCurlSeguro() !== self::CURLE_OPERATION_TIMEDOUT) {
                    throw $falha;
                }

                if ($tentativa === 2) {
                    throw new UnexpectedValueException();
                }

                $this->temporizador->aguardarMilissegundos(
                    $this->configuracaoLeitura->getEsperaRetryPadraoMs()
                );
            }
        }

        throw new UnexpectedValueException();
    }

    private function calcularEsperaRetryAfter(RespostaHttpPncp $resposta): int
    {
        $valores = $resposta->getHeaders()['retry-after'] ?? [];

        if (count($valores) !== 1) {
            return $this->configuracaoLeitura->getEsperaRetryPadraoMs();
        }

        $valor = trim($valores[0]);

        if (ctype_digit($valor)) {
            return $this->converterSegundosEmMilissegundosLimitados($valor);
        }

        $data = $this->decodificarDataHttp($valor);

        if ($data === null) {
            return $this->configuracaoLeitura->getEsperaRetryPadraoMs();
        }

        $diferencaSegundos = $data->getTimestamp()
            - $this->temporizador->agoraUtc()->getTimestamp();

        if ($diferencaSegundos <= 0) {
            return 0;
        }

        return min(
            $diferencaSegundos * 1_000,
            $this->configuracaoLeitura->getTetoRetryAfterMs()
        );
    }

    private function converterSegundosEmMilissegundosLimitados(string $segundos): int
    {
        $segundos = ltrim($segundos, '0');

        if ($segundos === '') {
            return 0;
        }

        $maximoSemExceder = intdiv(
            $this->configuracaoLeitura->getTetoRetryAfterMs(),
            1_000
        );
        $maximoTexto = (string) $maximoSemExceder;

        if (
            strlen($segundos) > strlen($maximoTexto)
            || (strlen($segundos) === strlen($maximoTexto) && strcmp($segundos, $maximoTexto) > 0)
        ) {
            return $this->configuracaoLeitura->getTetoRetryAfterMs();
        }

        return min(
            (int) $segundos * 1_000,
            $this->configuracaoLeitura->getTetoRetryAfterMs()
        );
    }

    private function decodificarDataHttp(string $valor): ?DateTimeImmutable
    {
        $fusoUtc = new DateTimeZone('UTC');
        $formatos = [
            '!D, d M Y H:i:s \G\M\T',
            '!l, d-M-y H:i:s \G\M\T',
            '!D M j H:i:s Y',
        ];

        foreach ($formatos as $formato) {
            $data = DateTimeImmutable::createFromFormat($formato, $valor, $fusoUtc);
            $erros = DateTimeImmutable::getLastErrors();

            if (
                $data instanceof DateTimeImmutable
                && ($erros === false || ($erros['warning_count'] === 0 && $erros['error_count'] === 0))
            ) {
                return $data;
            }
        }

        return null;
    }

    private function registrarTentativa(
        AcaoTecnicaPncp $acao,
        int $tentativa,
        ?int $pagina,
        ?int $statusHttp,
        ?string $codigoFalha = null
    ): void {
        $metadata = ['tentativa' => $tentativa];

        if ($pagina !== null) {
            $metadata['pagina'] = $pagina;
        }

        if ($statusHttp !== null) {
            $metadata['status_http'] = $statusHttp;
        }

        $contexto = ['action' => $acao->value, 'metadata' => $metadata];

        if ($codigoFalha !== null) {
            $contexto['error'] = ['code' => $codigoFalha];
        }

        $this->logger->info('Tentativa de leitura PNCP concluída.', $contexto);
    }

    private function exigirStatusOk(RespostaHttpPncp $resposta): void
    {
        if ($resposta->getStatus() !== 200) {
            throw new RuntimeException('Status de leitura PNCP inesperado.');
        }
    }

    /** @return array<string, mixed> */
    private function decodificarObjeto(RespostaHttpPncp $resposta): array
    {
        $valor = json_decode(
            $resposta->getCorpo(),
            true,
            $this->configuracaoLeitura->getProfundidadeMaximaJson(),
            JSON_THROW_ON_ERROR
        );

        if (!is_array($valor) || array_is_list($valor)) {
            throw new UnexpectedValueException();
        }

        return $valor;
    }

    /** @return array<int, mixed> */
    private function decodificarLista(RespostaHttpPncp $resposta): array
    {
        $valor = json_decode(
            $resposta->getCorpo(),
            true,
            $this->configuracaoLeitura->getProfundidadeMaximaJson(),
            JSON_THROW_ON_ERROR
        );

        if (!is_array($valor) || !array_is_list($valor)) {
            throw new UnexpectedValueException();
        }

        return $valor;
    }

    /**
     * @param array<string, mixed> $registro
     * @return array{uasg: string, sequencial: int}
     */
    private function validarIdentidade(
        array $registro,
        Cnpj $cnpjEsperado,
        int $anoEsperado,
        ?string $uasgEsperada = null,
        ?int $sequencialEsperado = null
    ): array {
        $cnpj = $this->normalizarCnpj($registro['cnpj'] ?? null);
        $ano = $registro['anoPca'] ?? null;
        $uasg = $registro['codigoUnidade'] ?? null;
        $sequencial = $registro['sequencialPca'] ?? null;

        if (
            !is_int($ano)
            || !is_string($uasg)
            || trim($uasg) === ''
            || !is_int($sequencial)
            || $sequencial <= 0
        ) {
            throw new UnexpectedValueException();
        }

        if (
            $cnpj !== $cnpjEsperado->getValor()
            || $ano !== $anoEsperado
            || ($uasgEsperada !== null && $uasg !== $uasgEsperada)
            || ($sequencialEsperado !== null && $sequencial !== $sequencialEsperado)
        ) {
            throw new DomainException();
        }

        return ['uasg' => $uasg, 'sequencial' => $sequencial];
    }

    private function normalizarCnpj(mixed $valor): string
    {
        if (!is_string($valor)) {
            throw new UnexpectedValueException();
        }

        try {
            return (new Cnpj($valor))->getValor();
        } catch (InvalidArgumentException) {
            throw new UnexpectedValueException();
        }
    }

    /** @return array<int, array{uasg: string, sequencial: int}> */
    private function lerTodosOsCandidatos(
        SessaoPncpAutenticada $sessao,
        string $caminhoCandidatos,
        Cnpj $cnpj,
        int $ano,
        AcaoTecnicaPncp $acao
    ): array {
        $candidatos = [];
        $sequenciaisVistos = [];
        $paginasVistas = [];
        $tamanhoPagina = $this->configuracaoLeitura->getTamanhoPagina();
        $limitePaginas = $this->configuracaoLeitura->getLimiteMaximoPaginas();

        for ($numeroPagina = 1; $numeroPagina <= $limitePaginas; $numeroPagina++) {
            $resposta = $this->get(
                $sessao,
                $caminhoCandidatos . '?pagina=' . $numeroPagina
                    . '&tamanhoPagina=' . $tamanhoPagina,
                $acao,
                $numeroPagina
            );

            if ($resposta->getStatus() === 204) {
                return $candidatos;
            }

            $this->exigirStatusOk($resposta);
            $pagina = $this->decodificarLista($resposta);

            if (count($pagina) > $tamanhoPagina) {
                throw new DomainException();
            }

            $chavesDaPagina = [];

            foreach ($pagina as $candidato) {
                if (!is_array($candidato) || array_is_list($candidato)) {
                    throw new UnexpectedValueException();
                }

                $identidade = $this->validarIdentidade($candidato, $cnpj, $ano);
                $sequencial = $identidade['sequencial'];

                if (isset($sequenciaisVistos[$sequencial])) {
                    throw new DomainException();
                }

                $sequenciaisVistos[$sequencial] = true;
                $candidatos[] = $identidade;
                $chavesDaPagina[] = $identidade['uasg'] . '|' . $sequencial;
            }

            $fingerprint = $this->fingerprintTextos($chavesDaPagina);

            if (isset($paginasVistas[$fingerprint])) {
                throw new DomainException();
            }

            $paginasVistas[$fingerprint] = true;

            if (count($pagina) < $tamanhoPagina) {
                return $candidatos;
            }

            if ($numeroPagina === $limitePaginas) {
                throw new UnexpectedValueException();
            }
        }

        throw new UnexpectedValueException();
    }

    /** @return int[] */
    private function lerTodosOsIds(
        SessaoPncpAutenticada $sessao,
        string $caminhoItens,
        Cnpj $cnpj,
        int $ano,
        string $uasg,
        int $sequencial,
        AcaoTecnicaPncp $acao
    ): array {
        $idsVistos = [];
        $paginasVistas = [];
        $tamanhoPagina = $this->configuracaoLeitura->getTamanhoPagina();
        $limitePaginas = $this->configuracaoLeitura->getLimiteMaximoPaginas();

        for ($numeroPagina = 1; $numeroPagina <= $limitePaginas; $numeroPagina++) {
            $resposta = $this->get(
                $sessao,
                $caminhoItens . '?pagina=' . $numeroPagina
                    . '&tamanhoPagina=' . $tamanhoPagina,
                $acao,
                $numeroPagina
            );

            if ($resposta->getStatus() === 204) {
                return array_keys($idsVistos);
            }

            $this->exigirStatusOk($resposta);
            $pagina = $this->decodificarLista($resposta);

            if (count($pagina) > $tamanhoPagina) {
                throw new DomainException();
            }

            $idsDaPagina = $this->lerIdsDaPagina(
                $pagina,
                $cnpj,
                $ano,
                $uasg,
                $sequencial
            );
            $fingerprint = $this->fingerprintPagina($idsDaPagina);

            if (isset($paginasVistas[$fingerprint])) {
                throw new DomainException();
            }

            $paginasVistas[$fingerprint] = true;

            foreach ($idsDaPagina as $id) {
                if (isset($idsVistos[$id])) {
                    throw new DomainException();
                }

                $idsVistos[$id] = true;
            }

            if (count($idsVistos) > self::QUANTIDADE_MAXIMA_ITENS) {
                throw new DomainException();
            }

            if (count($pagina) < $tamanhoPagina) {
                return array_keys($idsVistos);
            }

            if ($numeroPagina === $limitePaginas) {
                throw new UnexpectedValueException();
            }
        }

        throw new UnexpectedValueException();
    }

    /** @param int[] $ids */
    private function fingerprintPagina(array $ids): string
    {
        sort($ids, SORT_NUMERIC);

        return hash('sha256', implode(',', $ids));
    }

    /** @param string[] $valores */
    private function fingerprintTextos(array $valores): string
    {
        sort($valores, SORT_STRING);

        return hash('sha256', json_encode($valores, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<int, mixed> $pagina
     * @return int[]
     */
    private function lerIdsDaPagina(
        array $pagina,
        Cnpj $cnpj,
        int $ano,
        string $uasg,
        int $sequencial
    ): array {
        $ids = [];

        foreach ($pagina as $item) {
            if (!is_array($item) || array_is_list($item)) {
                throw new UnexpectedValueException();
            }

            $this->validarIdentidade($item, $cnpj, $ano, $uasg, $sequencial);
            $numeroItem = $item['numeroItem'] ?? null;

            if (
                !is_int($numeroItem)
                || $numeroItem <= 0
                || $numeroItem > self::NUMERO_ITEM_MAXIMO
            ) {
                throw new UnexpectedValueException();
            }

            if (isset($ids[$numeroItem])) {
                throw new DomainException();
            }

            $ids[$numeroItem] = true;
        }

        return array_keys($ids);
    }

    /** @param int[] $ids */
    private function observacaoEncontrada(
        SequencialPca $sequencial,
        array $ids
    ): ResultadoObservacaoPca {
        $this->logger->info('Leitura do PCA conhecido concluída.', [
            'action' => AcaoTecnicaPncp::OBSERVAR_PCA_PNCP->value,
            'metadata' => ['quantidade' => count($ids)],
        ]);

        return ResultadoObservacaoPca::encontrado(PcaObservado::ativo($sequencial, $ids));
    }

    private function buscaConcluidaSemCandidatos(): ResultadoBuscaCandidatosPca
    {
        return $this->buscaConcluida([]);
    }

    private function ausenciaDefinitivaObservacao(): ResultadoObservacaoPca
    {
        $this->logger->info('Ausência definitiva do PCA conhecido comprovada.', [
            'action' => AcaoTecnicaPncp::OBSERVAR_PCA_PNCP->value,
            'metadata' => ['quantidade' => 0],
        ]);

        return ResultadoObservacaoPca::ausenciaDefinitiva();
    }

    /** @param PcaObservado[] $candidatos */
    private function buscaConcluida(array $candidatos): ResultadoBuscaCandidatosPca
    {
        $this->logger->info('Busca de candidatos PNCP concluída.', [
            'action' => AcaoTecnicaPncp::LOCALIZAR_CANDIDATOS_PNCP->value,
            'metadata' => ['quantidade' => count($candidatos)],
        ]);

        return ResultadoBuscaCandidatosPca::concluida($candidatos);
    }

    private function inconclusivaObservacao(string $codigo, string $mensagem): ResultadoObservacaoPca
    {
        $this->registrarFalha(AcaoTecnicaPncp::OBSERVAR_PCA_PNCP, $codigo, $mensagem);

        return ResultadoObservacaoPca::inconclusiva(new FalhaSegura($codigo, $mensagem));
    }

    private function conflitoObservacao(string $codigo, string $mensagem): ResultadoObservacaoPca
    {
        $this->registrarFalha(AcaoTecnicaPncp::OBSERVAR_PCA_PNCP, $codigo, $mensagem);

        return ResultadoObservacaoPca::conflito(new FalhaSegura($codigo, $mensagem));
    }

    private function falhaSistemicaObservacao(string $codigo, string $mensagem): ResultadoObservacaoPca
    {
        $this->registrarFalha(AcaoTecnicaPncp::OBSERVAR_PCA_PNCP, $codigo, $mensagem);

        return ResultadoObservacaoPca::falhaSistemica(new FalhaSegura($codigo, $mensagem));
    }

    private function inconclusivaBusca(string $codigo, string $mensagem): ResultadoBuscaCandidatosPca
    {
        $this->registrarFalha(AcaoTecnicaPncp::LOCALIZAR_CANDIDATOS_PNCP, $codigo, $mensagem);

        return ResultadoBuscaCandidatosPca::inconclusiva(new FalhaSegura($codigo, $mensagem));
    }

    private function conflitoBusca(string $codigo, string $mensagem): ResultadoBuscaCandidatosPca
    {
        $this->registrarFalha(AcaoTecnicaPncp::LOCALIZAR_CANDIDATOS_PNCP, $codigo, $mensagem);

        return ResultadoBuscaCandidatosPca::conflito(new FalhaSegura($codigo, $mensagem));
    }

    private function falhaSistemicaBusca(string $codigo, string $mensagem): ResultadoBuscaCandidatosPca
    {
        $this->registrarFalha(AcaoTecnicaPncp::LOCALIZAR_CANDIDATOS_PNCP, $codigo, $mensagem);

        return ResultadoBuscaCandidatosPca::falhaSistemica(new FalhaSegura($codigo, $mensagem));
    }

    private function registrarFalha(AcaoTecnicaPncp $acao, string $codigo, string $mensagem): void
    {
        $this->logger->error($mensagem, [
            'action' => $acao->value,
            'error' => ['code' => $codigo],
        ]);
    }
}
