<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use DateTimeZone;
use InvalidArgumentException;
use Semae\Pca\Infrastructure\Oracle\Auditoria\MapeamentoHistoricosOracle;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConversorCharsetAdodbOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoLotacoesUsuarioOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoRepresentantesOracle;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\MapeamentoEstadoOperacionalOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoConfiguracaoEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoPlanosEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoDemandasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoLotacoesOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoPlanosOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoUnidadesOrcamentariasOracle;
use Semae\Pca\Infrastructure\Oracle\Transacao\MapeamentoControlesConcorrenciaOracle;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use Throwable;

final class PerfilPublicacaoPca
{
    private readonly AmbientePncp $ambiente;
    private readonly string $loginPncp;
    private readonly string $senhaPncp;
    private readonly ConfiguracaoTransportePncp $configuracaoTransporte;
    private readonly ConfiguracaoLeituraPncp $configuracaoLeitura;
    private readonly DateTimeZone $fusoOperacional;
    private readonly string $charsetAdodb;
    private readonly MapeamentoControlesConcorrenciaOracle $controlesConcorrencia;
    private readonly MapeamentoConfiguracaoEstrategiaOracle $configuracaoEstrategia;
    private readonly MapeamentoPlanosEstrategiaOracle $planosEstrategia;
    private readonly MapeamentoPlanosOracle $planos;
    private readonly MapeamentoDemandasOracle $demandas;
    private readonly MapeamentoUnidadesOrcamentariasOracle $unidadesOrcamentarias;
    private readonly MapeamentoLotacoesOracle $lotacoes;
    private readonly MapeamentoEstadoOperacionalOracle $estadoOperacional;
    private readonly MapeamentoRepresentantesOracle $representantes;
    private readonly MapeamentoLotacoesUsuarioOracle $lotacoesUsuario;
    private readonly MapeamentoHistoricosOracle $historicos;

    /** @param array<string, mixed> $perfil */
    public function __construct(array $perfil)
    {
        try {
            self::exigirChavesExatas($perfil, ['ambiente', 'cnpj', 'pncp', 'oracle']);
            self::exigirTextoNaoVazio($perfil, 'cnpj');

            $ambiente = self::exigirTexto($perfil, 'ambiente');
            $this->ambiente = AmbientePncp::tryFrom($ambiente)
                ?? throw new InvalidArgumentException('Ambiente PNCP desconhecido.');

            $pncp = self::exigirSecao($perfil, 'pncp');
            self::exigirChavesExatas($pncp, [
                'login',
                'senha',
                'ca_bundle',
                'limite_payload_bytes',
                'limite_resposta_bytes',
                'timeout_conexao_segundos',
                'timeout_login_segundos',
                'timeout_leitura_segundos',
                'timeout_escrita_segundos',
                'tamanho_pagina',
                'profundidade_maxima_json',
                'limite_maximo_paginas',
                'espera_retry_padrao_ms',
                'teto_retry_after_ms',
            ]);
            $this->loginPncp = self::exigirTextoNaoVazio($pncp, 'login');
            $this->senhaPncp = self::exigirTextoNaoVazio($pncp, 'senha');
            $caminhoCaBundle = self::exigirArquivoAbsolutoLegivelOpcional(
                $pncp,
                'ca_bundle'
            );
            $this->configuracaoTransporte = new ConfiguracaoTransportePncp(
                $this->ambiente,
                self::exigirInteiro($pncp, 'limite_payload_bytes'),
                self::exigirInteiro($pncp, 'limite_resposta_bytes'),
                self::exigirInteiro($pncp, 'timeout_conexao_segundos'),
                self::exigirInteiro($pncp, 'timeout_login_segundos'),
                self::exigirInteiro($pncp, 'timeout_leitura_segundos'),
                self::exigirInteiro($pncp, 'timeout_escrita_segundos'),
                $caminhoCaBundle
            );
            $this->configuracaoLeitura = new ConfiguracaoLeituraPncp(
                self::exigirInteiro($pncp, 'tamanho_pagina'),
                self::exigirInteiro($pncp, 'profundidade_maxima_json'),
                self::exigirInteiro($pncp, 'limite_maximo_paginas'),
                self::exigirInteiro($pncp, 'espera_retry_padrao_ms'),
                self::exigirInteiro($pncp, 'teto_retry_after_ms')
            );

            $oracle = self::exigirSecao($perfil, 'oracle');
            self::exigirChavesExatas($oracle, ['charset_adodb', 'timezone', 'mapeamentos']);
            $this->charsetAdodb = (new ConversorCharsetAdodbOracle(
                self::exigirTextoNaoVazio($oracle, 'charset_adodb')
            ))->getCharsetAdodb();
            $this->fusoOperacional = new DateTimeZone(
                self::exigirTextoNaoVazio($oracle, 'timezone')
            );
            $mapeamentos = self::exigirSecao($oracle, 'mapeamentos');
            $this->materializarMapeamentos($mapeamentos);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'O perfil de publicação é inválido.',
                0,
                $exception
            );
        }
    }

    public function getAmbiente(): AmbientePncp
    {
        return $this->ambiente;
    }

    public function getLoginPncp(): string
    {
        return $this->loginPncp;
    }

    public function getSenhaPncp(): string
    {
        return $this->senhaPncp;
    }

    public function getConfiguracaoTransporte(): ConfiguracaoTransportePncp
    {
        return $this->configuracaoTransporte;
    }

    public function getConfiguracaoLeitura(): ConfiguracaoLeituraPncp
    {
        return $this->configuracaoLeitura;
    }

    public function getFusoOperacional(): DateTimeZone
    {
        return $this->fusoOperacional;
    }

    public function getCharsetAdodb(): string
    {
        return $this->charsetAdodb;
    }

    public function getControlesConcorrencia(): MapeamentoControlesConcorrenciaOracle
    {
        return $this->controlesConcorrencia;
    }

    public function getConfiguracaoEstrategia(): MapeamentoConfiguracaoEstrategiaOracle
    {
        return $this->configuracaoEstrategia;
    }

    public function getPlanosEstrategia(): MapeamentoPlanosEstrategiaOracle
    {
        return $this->planosEstrategia;
    }

    public function getPlanos(): MapeamentoPlanosOracle
    {
        return $this->planos;
    }

    public function getDemandas(): MapeamentoDemandasOracle
    {
        return $this->demandas;
    }

    public function getUnidadesOrcamentarias(): MapeamentoUnidadesOrcamentariasOracle
    {
        return $this->unidadesOrcamentarias;
    }

    public function getLotacoes(): MapeamentoLotacoesOracle
    {
        return $this->lotacoes;
    }

    public function getEstadoOperacional(): MapeamentoEstadoOperacionalOracle
    {
        return $this->estadoOperacional;
    }

    public function getRepresentantes(): MapeamentoRepresentantesOracle
    {
        return $this->representantes;
    }

    public function getLotacoesUsuario(): MapeamentoLotacoesUsuarioOracle
    {
        return $this->lotacoesUsuario;
    }

    public function getHistoricos(): MapeamentoHistoricosOracle
    {
        return $this->historicos;
    }

    /** @param array<string, mixed> $mapeamentos */
    private function materializarMapeamentos(array $mapeamentos): void
    {
        $chaves = [
            'controles_concorrencia' => [
                'schema', 'tabela', 'colunaEscopo', 'colunaAnoExecucao',
                'colunaIdentificadorAlvo', 'identificadorAlvoEstrategiaAnual',
            ],
            'configuracao_estrategia' => [
                'schema', 'tabela', 'colunaChave', 'colunaValor', 'colunaDescricao',
                'chaveEstrategia', 'chaveUasgConsolidadora',
                'literalEstrategiaIndividual', 'literalEstrategiaConsolidada',
            ],
            'planos_estrategia' => [
                'schema', 'tabela', 'colunaAnoExecucao', 'colunaUnidadeOrcamentaria',
                'colunaEstrategiaAdotada', 'colunaUasgAdotada',
                'literalEstrategiaIndividual', 'literalEstrategiaConsolidada',
            ],
            'planos' => [
                'schema', 'tabela', 'colunaId', 'colunaUnidadeOrcamentaria',
                'colunaAnoExecucao', 'colunaVersao', 'colunaDataHomologacao',
                'colunaDataConfirmacao',
            ],
            'demandas' => [
                'schema', 'tabela', 'colunaId', 'colunaPlanoOrigem', 'colunaSituacao',
                'colunaUnidadeRequisitante', 'colunaDataDesejada', 'colunaCategoria',
                'colunaDescricao', 'colunaUnidadeMedida', 'colunaQuantidade',
                'colunaValorTotal', 'colunaValorOrcamentoExercicio',
                'colunaClassificacaoCatalogo',
                'colunaCodigoItem', 'colunaGrupo', 'grupoRemoto', 'colunaOrigem',
            ],
            'unidades_orcamentarias' => [
                'schema', 'tabela', 'colunaRelacaoPlano', 'colunaIdentidade',
                'colunaCodigoUasg', 'colunaDescricaoUasg', 'colunaAtivo', 'valorAtivo',
            ],
            'lotacoes' => ['schema', 'tabela', 'colunaId', 'colunaDescricao'],
            'estado_operacional' => [
                'schema', 'tabela', 'colunaUnidadeOrcamentaria', 'colunaAnoExecucao',
                'colunaEstrategia', 'colunaUasg', 'colunaEstadoPublicacao',
                'colunaSequencialPublicacaoPncp', 'literalEstrategiaIndividual',
                'literalEstrategiaConsolidada', 'literalNaoPublicado',
                'literalPublicado', 'literalExcluido',
            ],
            'representantes' => [
                'schema', 'tabela', 'colunaLogin', 'colunaId', 'colunaNome',
                'colunaAtivo', 'valorAtivo',
            ],
            'lotacoes_usuario' => [
                'schema', 'tabelaVinculos', 'tabelaLotacoes',
                'colunaFuncionarioVinculo', 'colunaLotacaoVinculo',
                'colunaFimVinculo', 'colunaIdLotacao', 'colunaNomeLotacao',
                'colunaAtivoLotacao', 'valorAtivo',
            ],
            'historicos' => [
                'schema', 'tabela', 'colunaInstante', 'colunaProcedimento',
                'colunaMensagem', 'colunaRepresentante', 'colunaLotacao',
            ],
        ];

        self::exigirChavesExatas($mapeamentos, array_keys($chaves));
        $grupos = [];

        foreach ($chaves as $nome => $campos) {
            $grupo = self::exigirSecao($mapeamentos, $nome);
            self::exigirChavesExatas($grupo, $campos);
            $grupos[$nome] = $grupo;
        }

        $this->controlesConcorrencia = new MapeamentoControlesConcorrenciaOracle(
            ...$grupos['controles_concorrencia']
        );
        $this->configuracaoEstrategia = new MapeamentoConfiguracaoEstrategiaOracle(
            ...$grupos['configuracao_estrategia']
        );
        $this->planosEstrategia = new MapeamentoPlanosEstrategiaOracle(
            ...$grupos['planos_estrategia']
        );
        $this->planos = new MapeamentoPlanosOracle(...$grupos['planos']);
        $this->demandas = new MapeamentoDemandasOracle(...$grupos['demandas']);
        $this->unidadesOrcamentarias = new MapeamentoUnidadesOrcamentariasOracle(
            ...$grupos['unidades_orcamentarias']
        );
        $this->lotacoes = new MapeamentoLotacoesOracle(...$grupos['lotacoes']);
        $this->estadoOperacional = new MapeamentoEstadoOperacionalOracle(
            ...$grupos['estado_operacional']
        );
        $this->representantes = new MapeamentoRepresentantesOracle(
            ...$grupos['representantes']
        );
        $this->lotacoesUsuario = new MapeamentoLotacoesUsuarioOracle(
            ...$grupos['lotacoes_usuario']
        );
        $this->historicos = new MapeamentoHistoricosOracle(...$grupos['historicos']);
    }

    /** @param array<string, mixed> $valores @param string[] $esperadas */
    private static function exigirChavesExatas(array $valores, array $esperadas): void
    {
        $recebidas = array_keys($valores);

        foreach ($recebidas as $recebida) {
            if (!is_string($recebida)) {
                throw new InvalidArgumentException('Chave de perfil inválida.');
            }
        }

        sort($recebidas, SORT_STRING);
        sort($esperadas, SORT_STRING);

        if ($recebidas !== $esperadas) {
            throw new InvalidArgumentException('Estrutura do perfil inválida.');
        }
    }

    /** @param array<string, mixed> $valores @return array<string, mixed> */
    private static function exigirSecao(array $valores, string $chave): array
    {
        $secao = $valores[$chave] ?? null;

        if (!is_array($secao)) {
            throw new InvalidArgumentException('Seção do perfil inválida.');
        }

        return $secao;
    }

    /** @param array<string, mixed> $valores */
    private static function exigirTexto(array $valores, string $chave): string
    {
        $valor = $valores[$chave] ?? null;

        if (!is_string($valor)) {
            throw new InvalidArgumentException('Texto do perfil inválido.');
        }

        return $valor;
    }

    /** @param array<string, mixed> $valores */
    private static function exigirTextoNaoVazio(array $valores, string $chave): string
    {
        $valor = self::exigirTexto($valores, $chave);

        if (trim($valor) === '') {
            throw new InvalidArgumentException('Texto obrigatório do perfil está vazio.');
        }

        return $valor;
    }

    /** @param array<string, mixed> $valores */
    private static function exigirInteiro(array $valores, string $chave): int
    {
        $valor = $valores[$chave] ?? null;

        if (!is_int($valor)) {
            throw new InvalidArgumentException('Inteiro do perfil inválido.');
        }

        return $valor;
    }

    /** @param array<string, mixed> $valores */
    private static function exigirArquivoAbsolutoLegivelOpcional(
        array $valores,
        string $chave
    ): ?string {
        $valor = $valores[$chave] ?? null;
        $campo = 'pncp.' . $chave;

        if ($valor === null) {
            return null;
        }
        if (!is_string($valor)) {
            throw new InvalidArgumentException(
                "{$campo} esperava null ou caminho absoluto legível; recebeu "
                    . get_debug_type($valor) . '.'
            );
        }

        $caminho = trim($valor);
        if ($caminho === '') {
            throw new InvalidArgumentException(
                "{$campo} esperava null ou caminho absoluto legível; recebeu texto vazio."
            );
        }

        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/D', $caminho) !== 1) {
            throw new InvalidArgumentException(
                "{$campo} deve ser um caminho absoluto; recebeu {$caminho}."
            );
        }

        $resolvido = realpath($caminho);

        if ($resolvido === false || !is_file($resolvido) || !is_readable($resolvido)) {
            throw new InvalidArgumentException(
                "{$campo} aponta para arquivo inexistente, irregular ou ilegível: {$caminho}."
            );
        }

        return $resolvido;
    }
}
