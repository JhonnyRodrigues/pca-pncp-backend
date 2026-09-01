<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Presentation\ViewModels\PublicarPcaViewModel;
use Semae\Pca\PublicadorPcaScriptcase;

final class PublicadorPcaScriptcaseIntegrationTest extends TestCase
{
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';
    private const SERVIDOR = 'pca.transversal.test';

    private string|false $registroAnterior;
    private string $diretorioTemporario;

    /** @var string[] */
    private array $arquivosTemporarios = [];

    protected function setUp(): void
    {
        $this->registroAnterior = getenv(self::VARIAVEL_REGISTRO);
        $base = tempnam(sys_get_temp_dir(), 'pca-s81-int-');

        if ($base === false || !unlink($base) || !mkdir($base)) {
            throw new RuntimeException('Não foi possível preparar o diretório temporário.');
        }

        $this->diretorioTemporario = $base;
        $perfilValido = $this->perfilValido();
        new PerfilPublicacaoPca($perfilValido);
        $perfil = $this->escreverRetornoPhp('perfil.php', $perfilValido);
        $registro = $this->escreverRetornoPhp('registro.php', [
            self::SERVIDOR => $perfil,
        ]);
        putenv(self::VARIAVEL_REGISTRO . '=' . $registro);
    }

    protected function tearDown(): void
    {
        if ($this->registroAnterior === false) {
            putenv(self::VARIAVEL_REGISTRO);
        } else {
            putenv(self::VARIAVEL_REGISTRO . '=' . $this->registroAnterior);
        }

        foreach (array_reverse($this->arquivosTemporarios) as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }

        if (is_dir($this->diretorioTemporario)) {
            rmdir($this->diretorioTemporario);
        }
    }

    public function testAnoInvalidoDevePararNoControllerSemIoExterno(): void
    {
        $adodb = new AdodbInterrompeAntesPncp();
        $requestId = '550E8400-E29B-41D4-A716-446655440000';

        $viewModel = $this->executar($adodb, (int) date('Y') - 1, $requestId);

        $this->assertSame('SOLICITACAO_INVALIDA', $viewModel->getSituacao());
        $this->assertSame('ANO_EXECUCAO_INVALIDO', $viewModel->getCodigoFalha());
        $this->assertSame('Ano de execução inválido.', $viewModel->getMensagemFalha());
        $this->assertSame($requestId, $viewModel->getCorrelacao());
        $this->assertSame(0, $adodb->consultas);
        $this->assertSame(0, $adodb->binds);
        $this->assertSame(0, $adodb->transacoes);
        $this->assertSame(0, $adodb->escritas);
        $this->assertFalse($adodb->fechada);
    }

    public function testDuasChamadasDevemAlcancarApplicationEPararAntesDoPncp(): void
    {
        $adodb = new AdodbInterrompeAntesPncp();
        $primeiroRequestId = '550E8400-E29B-41D4-A716-446655440000';
        $segundoRequestId = '6ba7b810-9dad-41d1-80b4-00c04fd430c8';

        $primeiro = $this->executar($adodb, (int) date('Y'), $primeiroRequestId);
        $segundo = $this->executar($adodb, (int) date('Y'), $segundoRequestId);

        $this->assertInterrupcaoContexto($primeiro, $primeiroRequestId);
        $this->assertInterrupcaoContexto($segundo, $segundoRequestId);
        $this->assertSame(2, $adodb->consultas);
        $this->assertSame(6, $adodb->binds);
        $this->assertSame(0, $adodb->transacoes);
        $this->assertSame(0, $adodb->escritas);
        $this->assertFalse($adodb->fechada);
        $this->assertCount(2, $adodb->parametrosConsultas);
        $this->assertSame('usuario.integracao', $adodb->parametrosConsultas[0]['param_0']);
        $this->assertSame('usuario.integracao', $adodb->parametrosConsultas[1]['param_0']);
    }

    private function executar(
        AdodbInterrompeAntesPncp $adodb,
        int $ano,
        string $requestId
    ): PublicarPcaViewModel {
        return (new PublicadorPcaScriptcase())->executar(
            $adodb,
            'usuario.integracao',
            self::SERVIDOR,
            $ano,
            $requestId
        );
    }

    private function assertInterrupcaoContexto(
        PublicarPcaViewModel $viewModel,
        string $requestId
    ): void {
        $this->assertSame('INTERROMPIDA', $viewModel->getSituacao());
        $this->assertSame(
            'REPRESENTANTE_USUARIO_NAO_RESOLVIDO',
            $viewModel->getCodigoFalha()
        );
        $this->assertSame(
            'O login autenticado não corresponde a um Representante ativo único.',
            $viewModel->getMensagemFalha()
        );
        $this->assertSame([], $viewModel->getResultadosUnitarios());
        $this->assertSame(0, $viewModel->getQuantidadePlanejadas());
        $this->assertSame(0, $viewModel->getQuantidadeVisitadas());
        $this->assertSame(0, $viewModel->getQuantidadeNaoProcessadas());
        $this->assertSame($requestId, $viewModel->getCorrelacao());
    }

    /** @return array<string, mixed> */
    private function perfilValido(): array
    {
        return [
            'ambiente' => 'HOMOLOGACAO',
            'cnpj' => '12.345.678/0001-95',
            'pncp' => [
                // Se a ordem regredir e alcançar a autenticação, o JSON falha antes do transporte.
                'login' => "login-\xB1",
                'senha' => 'senha-ficticia',
                'ca_bundle' => null,
                'limite_payload_bytes' => 100_000,
                'limite_resposta_bytes' => 100_000,
                'timeout_conexao_segundos' => 5,
                'timeout_login_segundos' => 30,
                'timeout_leitura_segundos' => 30,
                'timeout_escrita_segundos' => 60,
                'tamanho_pagina' => 100,
                'profundidade_maxima_json' => 64,
                'limite_maximo_paginas' => 100,
                'espera_retry_padrao_ms' => 250,
                'teto_retry_after_ms' => 5_000,
            ],
            'oracle' => [
                'timezone' => 'America/Sao_Paulo',
                'mapeamentos' => $this->mapeamentos(),
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function mapeamentos(): array
    {
        return [
            'controles_concorrencia' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaEscopo', 'colunaAnoExecucao',
                'colunaIdentificadorAlvo', 'identificadorAlvoEstrategiaAnual',
            ]),
            'configuracao_estrategia' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaChave', 'colunaValor', 'colunaDescricao',
                'chaveEstrategia', 'chaveUasgConsolidadora',
                'literalEstrategiaIndividual', 'literalEstrategiaConsolidada',
            ]),
            'planos_estrategia' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaAnoExecucao', 'colunaUnidadeOrcamentaria',
                'colunaEstrategiaAdotada', 'colunaUasgAdotada',
                'literalEstrategiaIndividual', 'literalEstrategiaConsolidada',
            ]),
            'planos' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaId', 'colunaUnidadeOrcamentaria',
                'colunaAnoExecucao', 'colunaVersao', 'colunaDataHomologacao',
                'colunaDataConfirmacao',
            ]),
            'demandas' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaId', 'colunaPlanoOrigem', 'colunaSituacao',
                'colunaUnidadeRequisitante', 'colunaDataDesejada', 'colunaCategoria',
                'colunaDescricao', 'colunaUnidadeMedida', 'colunaQuantidade',
                'colunaValorTotal', 'colunaValorOrcamentoExercicio', 'colunaCatalogo',
                'colunaCodigoItem', 'colunaGrupo', 'grupoRemoto', 'colunaOrigem',
            ]),
            'unidades_orcamentarias' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaRelacaoPlano', 'colunaIdentidade',
                'colunaCodigoUasg', 'colunaDescricaoUasg', 'colunaAtivo', 'valorAtivo',
            ]),
            'lotacoes' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaId', 'colunaDescricao',
            ]),
            'estado_operacional' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaUnidadeOrcamentaria', 'colunaAnoExecucao',
                'colunaEstrategia', 'colunaUasg', 'colunaEstadoPublicacao',
                'colunaSequencialPublicacaoPncp', 'literalEstrategiaIndividual',
                'literalEstrategiaConsolidada', 'literalNaoPublicado',
                'literalPublicado', 'literalExcluido',
            ]),
            'representantes' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaLogin', 'colunaId', 'colunaNome',
                'colunaAtivo', 'valorAtivo',
            ]),
            'lotacoes_usuario' => $this->grupoMapeamento([
                'schema', 'tabelaVinculos', 'tabelaLotacoes',
                'colunaFuncionarioVinculo', 'colunaLotacaoVinculo',
                'colunaFimVinculo', 'colunaIdLotacao', 'colunaNomeLotacao',
                'colunaAtivoLotacao', 'valorAtivo',
            ]),
            'historicos' => $this->grupoMapeamento([
                'schema', 'tabela', 'colunaInstante', 'colunaProcedimento',
                'colunaMensagem', 'colunaRepresentante', 'colunaLotacao',
            ]),
        ];
    }

    /** @param string[] $campos @return array<string, mixed> */
    private function grupoMapeamento(array $campos): array
    {
        $grupo = [];

        foreach ($campos as $campo) {
            $grupo[$campo] = match ($campo) {
                'valorAtivo' => 'ATIVO',
                'grupoRemoto' => [
                    'schema' => 'GRUPO_SCHEMA',
                    'tabela' => 'GRUPO_TABELA',
                    'dblink' => 'READ_ORCL',
                    'colunaCodigo' => 'GRUPO_CODIGO',
                    'colunaDescricao' => 'GRUPO_DESCRICAO',
                ],
                default => strtoupper((string) preg_replace(
                    '/(?<!^)[A-Z]/',
                    '_$0',
                    $campo
                )),
            };
        }

        return $grupo;
    }

    /** @param mixed $retorno */
    private function escreverRetornoPhp(string $nome, mixed $retorno): string
    {
        $caminho = $this->diretorioTemporario . DIRECTORY_SEPARATOR . $nome;
        $conteudo = "<?php\n\nreturn " . var_export($retorno, true) . ";\n";

        if (file_put_contents($caminho, $conteudo) === false) {
            throw new RuntimeException('Não foi possível escrever arquivo temporário.');
        }

        $this->arquivosTemporarios[] = $caminho;

        return $caminho;
    }
}

final class AdodbInterrompeAntesPncp
{
    public int $consultas = 0;
    public int $binds = 0;
    public int $transacoes = 0;
    public int $escritas = 0;
    public bool $fechada = false;

    /** @var array<int, array<string, mixed>> */
    public array $parametrosConsultas = [];

    public function BeginTrans(): bool
    {
        $this->transacoes++;

        return true;
    }

    public function CommitTrans(): bool
    {
        $this->transacoes++;

        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->transacoes++;

        return true;
    }

    /** @param mixed[] $parametros */
    public function Execute(string $sql, array $parametros = []): bool
    {
        $this->escritas++;

        return true;
    }

    /** @param mixed[] $parametros @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $parametros = []): array
    {
        $this->consultas++;
        $this->parametrosConsultas[] = $parametros;

        return [];
    }

    public function Param(string|false $nome): string
    {
        $this->binds++;

        return ':' . ($nome === false ? 'ignorado' : $nome);
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }

    public function Close(): void
    {
        $this->fechada = true;
    }
}
