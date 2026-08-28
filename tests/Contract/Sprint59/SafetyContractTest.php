<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Tests\Contract\Sprint59\Support\BorrowedAdodbGuard;
use Tests\Contract\Sprint59\Support\SafeContractReport;
use Tests\Contract\Sprint59\Support\Sprint59Config;

final class SafetyContractTest extends TestCase
{
    public function testAmbienteDeProducaoDeveContinuarDiferenteDoTreinamento(): void
    {
        $this->assertSame('https://treina.pncp.gov.br/api/pncp', AmbientePncp::HOMOLOGACAO->getBaseUrl());
        $this->assertNotSame(AmbientePncp::PRODUCAO->getBaseUrl(), AmbientePncp::HOMOLOGACAO->getBaseUrl());
    }

    public function testPreflightPncpDeveFalharFechadoSemConsentimento(): void
    {
        $config = $this->validPncpConfig();
        $config['allow_writes'] = false;

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::pncp(['pncp' => $config]);
    }

    public function testPreflightPncpDeveRecusarDestinoConfiguravel(): void
    {
        $config = $this->validPncpConfig();
        $config['base_url'] = AmbientePncp::PRODUCAO->getBaseUrl();

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::pncp(['pncp' => $config]);
    }

    public function testPreflightPncpDeveRecusarIdsContiguos(): void
    {
        $config = $this->validPncpConfig();
        $config['id_demanda_publicacao_2'] = $config['id_demanda_publicacao_1'] + 1;

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::pncp(['pncp' => $config]);
    }

    public function testPreflightPncpDeveRecusarOptInDe1001Itens(): void
    {
        $config = $this->validPncpConfig();
        $config['allow_large_batch'] = true;

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::pncp(['pncp' => $config]);
    }

    public function testPreflightOracleDeveExigirTodosOsMapeamentos(): void
    {
        $config = $this->validOracleConfig();
        unset($config['mappings']['historicos']);

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::oracle(['oracle' => $config]);
    }

    public function testPreflightOracleDeveExigirMapeamentoSeparadoDeLotacoes(): void
    {
        $config = $this->validOracleConfig();
        unset($config['mappings']['lotacoes']);

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::oracle(['oracle' => $config]);
    }

    public function testPreflightOracleDeveExigirControlesConcorrencia(): void
    {
        $config = $this->validOracleConfig();
        unset($config['mappings']['controles_concorrencia']);

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::oracle(['oracle' => $config]);
    }

    public function testPreflightOracleNaoDeveAceitarAncorasComoAlias(): void
    {
        $config = $this->validOracleConfig();
        $config['mappings']['ancoras'] = $config['mappings']['controles_concorrencia'];

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::oracle(['oracle' => $config]);
    }

    public function testPreflightOracleDeveRecusarDblinkDeGrupoNaoAutorizado(): void
    {
        $config = $this->validOracleConfig();
        $config['mappings']['demandas']['grupoRemoto']['dblink'] = 'OUTRO_LINK';

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::oracle(['oracle' => $config]);
    }

    public function testPreflightOracleDeveRecusarContratoAntigoDeDescricaoLocal(): void
    {
        $config = $this->validOracleConfig();
        unset($config['mappings']['demandas']['grupoRemoto']);
        $config['mappings']['demandas']['colunaDescricaoGrupo'] = 'DESCRICAO_GRUPO';

        $this->expectException(InvalidArgumentException::class);
        Sprint59Config::oracle(['oracle' => $config]);
    }

    public function testRelatorioDeveRecusarSegredo(): void
    {
        $this->expectException(RuntimeException::class);
        SafeContractReport::encode(['status' => 'ok', 'detalhe' => 'senha-real'], ['senha-real']);
    }

    public function testRelatorioDeveRecusarCampoSensivel(): void
    {
        $this->expectException(RuntimeException::class);
        SafeContractReport::encode(['status' => 'ok', 'headers' => []], []);
    }

    public function testEntrypointOracleDevePreservarAssinaturaAprovada(): void
    {
        require_once __DIR__ . '/scriptcase_probe.php';
        $function = new \ReflectionFunction('executar_contratos_adapters_oracle_sprint_59');

        $this->assertSame(
            ['db', 'login', 'mode'],
            array_map(
                static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
                $function->getParameters()
            )
        );
        $this->assertSame('adapters', $function->getParameters()[2]->getDefaultValue());
    }

    public function testRelatorioSeguroDeveSerEstavelParaHash(): void
    {
        $report = ['sprint' => '5.9', 'confirmado' => false, 'gate' => 'CONTROLADO'];

        $this->assertSame(SafeContractReport::hash($report), SafeContractReport::hash($report));
        $this->assertStringNotContainsString('segredo', SafeContractReport::encode($report, ['segredo']));
    }

    public function testGuardaDeConexaoEmprestadaDeveImpedirClose(): void
    {
        $inner = new class {
            public bool $closed = false;

            public function Close(): void
            {
                $this->closed = true;
            }
        };
        $guard = new BorrowedAdodbGuard($inner);

        try {
            $guard->Close();
            $this->fail('Close deveria ter sido impedido.');
        } catch (\LogicException) {
            $this->assertTrue($guard->wasCloseCalled());
            $this->assertFalse($inner->closed);
        }
    }

    /** @return array<string, mixed> */
    private function validPncpConfig(): array
    {
        $year = (int) date('Y') + 1;

        return [
            'ambiente' => 'HOMOLOGACAO',
            'allow_writes' => true,
            'ca_bundle' => null,
            'login' => 'usuario-secreto',
            'senha' => 'senha-secreta',
            'cnpj' => '12345678000195',
            'codigo_uasg' => '986789',
            'descricao_uasg' => 'Unidade de treinamento',
            'ano' => $year,
            'data_desejada' => $year . '-06-30',
            'id_demanda_publicacao_1' => 51001,
            'id_demanda_publicacao_2' => 51004,
            'id_demanda_inclusao' => 51009,
            'owned_residue_sequence' => null,
            'limite_payload_bytes' => 8388608,
            'limite_resposta_bytes' => 8388608,
            'timeout_conexao' => 5,
            'timeout_login' => 30,
            'timeout_leitura' => 30,
            'timeout_escrita' => 60,
            'page_size' => 100,
            'max_pages' => 100,
            'item_template' => [
                'categoria' => 1,
                'catalogo' => 2,
                'unidade_medida' => 'UNIDADE',
                'codigo_item' => 'ITEM-1',
                'grupo' => 'GRUPO-1',
                'descricao_grupo' => 'Grupo de teste',
                'origem' => 'SPRINT_5_9',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function validOracleConfig(): array
    {
        return [
            'allow_writes' => true,
            'timezone' => 'America/Sao_Paulo',
            'ano' => 2027,
            'id_unidade_orcamentaria' => 1,
            'sequencial_teste' => 59,
            'mensagem_auditoria' => 'PCA-S59-CONTRATO-ADAPTERS',
            'lock_hold_seconds' => 30,
            'mappings' => [
                'controles_concorrencia' => [
                    'schema' => 'SGI', 'tabela' => 'PAC_CONTROLES_CONCORRENCIA',
                    'colunaEscopo' => 'ESCOPO', 'colunaAnoExecucao' => 'ANO_EXECUCAO',
                    'colunaIdentificadorAlvo' => 'IDENTIFICADOR_ALVO',
                    'identificadorAlvoEstrategiaAnual' => 'ESTRATEGIA_ANUAL',
                ],
                'configuracao_estrategia' => [
                    'schema' => 'PCA_TESTE', 'tabela' => 'CONFIGURACAO', 'colunaChave' => 'CHAVE',
                    'colunaValor' => 'VALOR', 'colunaDescricao' => 'DESCRICAO',
                    'chaveEstrategia' => 'ESTRATEGIA_PUBLICACAO_PLANOS',
                    'chaveUasgConsolidadora' => 'UASG_CONSOLIDADORA',
                    'literalEstrategiaIndividual' => 'INDIVIDUAL',
                    'literalEstrategiaConsolidada' => 'CONSOLIDADO',
                ],
                'planos_estrategia' => [
                    'schema' => 'PCA_TESTE', 'tabela' => 'PLANOS', 'colunaAnoExecucao' => 'ANO',
                    'colunaUnidadeOrcamentaria' => 'UO',
                    'colunaEstrategiaAdotada' => 'ESTRATEGIA_ADOTADA_PUBLICACAO',
                    'colunaUasgAdotada' => 'UASG_ADOTADA',
                    'literalEstrategiaIndividual' => 'INDIVIDUAL',
                    'literalEstrategiaConsolidada' => 'CONSOLIDADO',
                ],
                'planos' => [
                    'schema' => 'PCA_TESTE', 'tabela' => 'PLANOS', 'colunaId' => 'ID',
                    'colunaUnidadeOrcamentaria' => 'UO', 'colunaAnoExecucao' => 'ANO',
                    'colunaVersao' => 'VERSAO', 'colunaDataHomologacao' => 'HOMOLOGACAO',
                    'colunaDataConfirmacao' => 'CONFIRMACAO',
                ],
                'demandas' => [
                    'schema' => 'PCA_TESTE', 'tabela' => 'DEMANDAS', 'colunaId' => 'ID',
                    'colunaPlanoOrigem' => 'PLANO', 'colunaSituacao' => 'SITUACAO',
                    'colunaUnidadeRequisitante' => 'REQUISITANTE', 'colunaDataDesejada' => 'DATA_DESEJADA',
                    'colunaCategoria' => 'CATEGORIA', 'colunaDescricao' => 'DESCRICAO',
                    'colunaUnidadeMedida' => 'UNIDADE_MEDIDA', 'colunaQuantidade' => 'QUANTIDADE',
                    'colunaValorTotal' => 'VALOR_TOTAL', 'colunaValorOrcamentoExercicio' => 'VALOR_ORCAMENTO',
                    'colunaCatalogo' => 'CATALOGO', 'colunaCodigoItem' => 'CODIGO_ITEM',
                    'colunaGrupo' => 'GRUPO_CODIGO',
                    'grupoRemoto' => [
                        'schema' => 'CEBISIS', 'tabela' => 'MT_GRUPOS_MATERIAIS',
                        'dblink' => 'READ_ORCL', 'colunaCodigo' => 'CODIGO',
                        'colunaDescricao' => 'DESCRICAO',
                    ],
                    'colunaOrigem' => 'ORIGEM',
                ],
                'unidades_orcamentarias' => [
                    'schema' => 'PCA_TESTE', 'tabela' => 'UNIDADES',
                    'colunaRelacaoPlano' => 'ID_UNIDADE_ORCAMENTARIA',
                    'colunaIdentidade' => 'ID_UNIDADE_ORCAMENTARIA',
                    'colunaCodigoUasg' => 'UASG', 'colunaDescricaoUasg' => 'DESCRICAO_UASG',
                    'colunaAtivo' => 'ATIVO', 'valorAtivo' => 'S',
                ],
                'lotacoes' => [
                    'schema' => 'PCA_TESTE', 'tabela' => 'LOTACOES',
                    'colunaId' => 'COD_LOTACAO', 'colunaDescricao' => 'NOME_LOTACAO',
                ],
                'estado_operacional' => [
                    'schema' => 'SGI', 'tabela' => 'PAC_PLANOS',
                    'colunaUnidadeOrcamentaria' => 'FK_UNIDADE_ORCAMENTARIA',
                    'colunaAnoExecucao' => 'ANO_EXERCICIO',
                    'colunaEstrategia' => 'ESTRATEGIA_ADOTADA_PUBLICACAO',
                    'colunaUasg' => 'UASG_ADOTADA',
                    'colunaEstadoPublicacao' => 'ESTADO_PUBLICACAO',
                    'colunaSequencialPublicacaoPncp' => 'SEQUENCIAL_PUBLICACAO_PNCP',
                    'literalEstrategiaIndividual' => 'INDIVIDUAL',
                    'literalEstrategiaConsolidada' => 'CONSOLIDADO',
                    'literalNaoPublicado' => 'NAO_PUBLICADO', 'literalPublicado' => 'PUBLICADO',
                    'literalExcluido' => 'EXCLUIDO',
                ],
                'representantes' => [
                    'schema' => 'PCA_TESTE', 'tabela' => 'REPRESENTANTES', 'colunaLogin' => 'LOGIN',
                    'colunaId' => 'ID', 'colunaNome' => 'NOME', 'colunaAtivo' => 'ATIVO', 'valorAtivo' => 'S',
                ],
                'lotacoes_usuario' => [
                    'schema' => 'PCA_TESTE', 'tabelaVinculos' => 'VINCULOS', 'tabelaLotacoes' => 'LOTACOES',
                    'colunaFuncionarioVinculo' => 'FUNCIONARIO', 'colunaLotacaoVinculo' => 'LOTACAO',
                    'colunaFimVinculo' => 'FIM', 'colunaIdLotacao' => 'ID',
                    'colunaNomeLotacao' => 'NOME', 'colunaAtivoLotacao' => 'ATIVO', 'valorAtivo' => 'S',
                ],
                'historicos' => [
                    'schema' => 'PCA_TESTE', 'tabela' => 'HISTORICOS', 'colunaInstante' => 'INSTANTE',
                    'colunaProcedimento' => 'PROCEDIMENTO', 'colunaMensagem' => 'MENSAGEM',
                    'colunaRepresentante' => 'REPRESENTANTE', 'colunaLotacao' => 'LOTACAO',
                ],
            ],
        ];
    }
}
