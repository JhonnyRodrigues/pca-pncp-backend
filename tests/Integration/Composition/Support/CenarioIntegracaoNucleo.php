<?php

declare(strict_types=1);

namespace Tests\Integration\Composition\Support;

use DateTimeZone;
use Psr\Log\NullLogger;
use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Application\UseCases\PublicarPca\PublicarPcaRequest;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Composition\MontadorNucleoPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\AnoExecucao;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Auditoria\MapeamentoHistoricosOracle;
use Semae\Pca\Infrastructure\Oracle\Auditoria\OracleRegistroAuditoriaAdapter;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoLotacoesUsuarioOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\MapeamentoRepresentantesOracle;
use Semae\Pca\Infrastructure\Oracle\Contexto\OracleContextoUsuarioAdapter;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\MapeamentoEstadoOperacionalOracle;
use Semae\Pca\Infrastructure\Oracle\EstadoOperacional\OracleEstadoOperacionalPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoConfiguracaoEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\MapeamentoPlanosEstrategiaOracle;
use Semae\Pca\Infrastructure\Oracle\Estrategia\OracleEstrategiaPublicacaoAdapter;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoDemandasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoLotacoesOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoPlanosOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\MapeamentoUnidadesOrcamentariasOracle;
use Semae\Pca\Infrastructure\Oracle\Plano\OraclePlanoRepository;
use Semae\Pca\Infrastructure\Oracle\Transacao\MapeamentoControlesConcorrenciaOracle;
use Semae\Pca\Infrastructure\Oracle\Transacao\OracleUnidadeDeTrabalhoAdapter;
use Semae\Pca\Infrastructure\Pncp\Autenticacao\AutenticadorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Escrita\EscritorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Semae\Pca\Infrastructure\Pncp\Http\ConfiguracaoTransportePncp;
use Semae\Pca\Infrastructure\Pncp\Http\TransporteCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\ConfiguracaoLeituraPncp;
use Semae\Pca\Infrastructure\Pncp\Leitura\LeitorPncpAdapter;
use Semae\Pca\Infrastructure\Pncp\Leitura\TemporizadorSistemaPncp;
use Semae\Pca\Infrastructure\Pncp\Serializacao\SerializadorPayloadPncp;

final class CenarioIntegracaoNucleo
{
    private const ANO = 2027;
    private const CNPJ = '12345678000195';
    private const UASG = '928344';
    private const BASE_PNCP = 'https://treina.pncp.gov.br/api/pncp';

    private function __construct(
        public readonly RegistroCronologico $eventos,
        public readonly AdodbControlado $adodb,
        public readonly ExecutorCurlRoteirizado $curl,
        public readonly ?TemporizadorControlado $temporizador,
        private readonly MontadorNucleoPublicacaoPca $montador
    ) {}

    public static function individualJaConvergente(): self
    {
        $eventos = new RegistroCronologico();
        $adodb = new AdodbControlado($eventos);
        self::programarOracleIndividual($adodb, 1001, '2026-08-21 09:00:00');
        $curl = new ExecutorCurlRoteirizado($eventos);
        self::programarPncpJaConvergente($curl);

        return self::criar($eventos, $adodb, $curl);
    }

    public static function individualParaRetificacao(): self
    {
        $eventos = new RegistroCronologico();
        $adodb = new AdodbControlado($eventos);
        self::programarOracleIndividual($adodb, 1002, null);
        $curl = new ExecutorCurlRoteirizado($eventos);
        self::programarPncpRetificacao($curl);

        return self::criar($eventos, $adodb, $curl);
    }

    public static function individualParaRetificacaoInconclusiva(): self
    {
        $eventos = new RegistroCronologico();
        $adodb = new AdodbControlado($eventos);
        self::programarOracleIndividual($adodb, 1002, null, false);
        $curl = new ExecutorCurlRoteirizado($eventos);
        self::programarPncpRetificacaoInconclusiva($curl);
        $temporizador = new TemporizadorControlado();

        return self::criar($eventos, $adodb, $curl, $temporizador);
    }

    public static function controlado(
        RegistroCronologico $eventos,
        AdodbControlado $adodb,
        ExecutorCurlRoteirizado $curl
    ): self {
        return self::criar($eventos, $adodb, $curl);
    }

    private static function criar(
        RegistroCronologico $eventos,
        AdodbControlado $adodb,
        ExecutorCurlRoteirizado $curl,
        ?TemporizadorControlado $temporizador = null
    ): self {
        $conexao = new ConexaoAdodbEmprestada($adodb);
        $logger = new NullLogger();
        $configuracaoTransporte = new ConfiguracaoTransportePncp(
            AmbientePncp::HOMOLOGACAO,
            100_000,
            100_000
        );
        $transporte = new TransporteCurlPncp($configuracaoTransporte, $curl);

        $montador = new MontadorNucleoPublicacaoPca(
            new OracleContextoUsuarioAdapter(
                $conexao,
                new MapeamentoRepresentantesOracle(
                    'SGI', 'RH_FUNCIONARIOS', 'NOME_USUARIO', 'COD_FUNCIONARIO',
                    'NOME_CIVIL', 'ATIVO', 1
                ),
                new MapeamentoLotacoesUsuarioOracle(
                    'SGI', 'RH_FUNCIONARIOS_LOTACOES', 'RH_LOTACOES',
                    'COD_FUNCIONARIO', 'COD_LOTACAO', 'DT_FIM_LOTACAO',
                    'COD_LOTACAO', 'NOME_LOTACAO', 'ATIVO', 1
                ),
                'usuario.teste'
            ),
            new OraclePlanoRepository(
                $conexao,
                self::mapeamentoPlanos(),
                self::mapeamentoDemandas(),
                self::mapeamentoUnidades(),
                new MapeamentoLotacoesOracle(
                    'SGI', 'RH_LOTACOES', 'COD_LOTACAO', 'NOME_LOTACAO'
                ),
                new DateTimeZone('America/Sao_Paulo')
            ),
            new OracleEstrategiaPublicacaoAdapter(
                $conexao,
                new MapeamentoConfiguracaoEstrategiaOracle(
                    'SGI', 'PAC_CONFIGURACOES', 'CHAVE', 'VALOR', 'DESCRICAO',
                    'ESTRATEGIA_PUBLICACAO_PLANOS', 'UASG_CONSOLIDADORA',
                    'INDIVIDUAL', 'CONSOLIDADO'
                ),
                new MapeamentoPlanosEstrategiaOracle(
                    'SGI', 'PAC_PLANOS', 'ANO_EXERCICIO', 'FK_UNIDADE_ORCAMENTARIA',
                    'ESTRATEGIA_ADOTADA_PUBLICACAO', 'UASG_ADOTADA',
                    'INDIVIDUAL', 'CONSOLIDADO'
                ),
                self::mapeamentoUnidades()
            ),
            new OracleUnidadeDeTrabalhoAdapter(
                $conexao,
                new MapeamentoControlesConcorrenciaOracle(
                    'SGI', 'PAC_CONTROLES_CONCORRENCIA',
                    'ESCOPO', 'ANO_EXECUCAO', 'IDENTIFICADOR_ALVO', 'ESTRATEGIA_ANUAL'
                )
            ),
            new AutenticadorPncpAdapter(
                $transporte,
                $configuracaoTransporte,
                $logger,
                'login-ficticio',
                'senha-ficticia'
            ),
            new LeitorPncpAdapter(
                $transporte,
                $configuracaoTransporte,
                new ConfiguracaoLeituraPncp(100, 32, 1, 1, 1),
                $logger,
                $temporizador ?? new TemporizadorSistemaPncp()
            ),
            new EscritorPncpAdapter(
                $transporte,
                $configuracaoTransporte,
                new SerializadorPayloadPncp(),
                $logger
            ),
            new OracleEstadoOperacionalPublicacaoAdapter(
                $conexao,
                new MapeamentoEstadoOperacionalOracle(
                    'SGI', 'PAC_PLANOS', 'FK_UNIDADE_ORCAMENTARIA', 'ANO_EXERCICIO',
                    'ESTRATEGIA_ADOTADA_PUBLICACAO', 'UASG_ADOTADA',
                    'ESTADO_PUBLICACAO', 'SEQUENCIAL_PUBLICACAO_PNCP',
                    'INDIVIDUAL', 'CONSOLIDADO',
                    'NAO_PUBLICADO', 'PUBLICADO', 'EXCLUIDO'
                )
            ),
            new OracleRegistroAuditoriaAdapter(
                $conexao,
                new MapeamentoHistoricosOracle(
                    'SGI', 'PAC_HISTORICOS', 'DATA_REGISTRO', 'PROCEDIMENTO',
                    'MENSAGEM', 'FK_REPRESENTANTE', 'FK_LOTACAO'
                )
            ),
            $logger
        );

        return new self($eventos, $adodb, $curl, $temporizador, $montador);
    }

    public function executar(): PublicarPcaResponse
    {
        return $this->montador->montar()->executar(
            new PublicarPcaRequest(new Cnpj(self::CNPJ), new AnoExecucao(self::ANO)),
            new CorrelacaoExecucao('integracao-sprint-6-2')
        );
    }

    private static function programarOracleIndividual(
        AdodbControlado $adodb,
        int $idDemanda,
        ?string $dataConfirmacao,
        bool $confirmacaoEsperada = true
    ): void {
        $identidade = [[
            'CHAVE_UO_PLANO' => '101',
            'IDENTIDADE_UO' => 'UO-101',
            'ID_LOTACAO' => '101',
            'DESCRICAO_LOTACAO' => 'Lotacao de teste',
            'DESCRICAO_UO' => 'Unidade Orcamentaria de teste',
            'CODIGO_UASG' => self::UASG,
            'DESCRICAO_UASG' => 'UASG de teste',
        ]];

        $adodb->programarConsulta('contexto_representante', [[
            'ID_REPRESENTANTE' => '709',
            'NOME_REPRESENTANTE' => 'Representante de teste',
        ]]);
        $adodb->programarConsulta('contexto_lotacao', [[
            'ID_LOTACAO' => '159',
            'NOME_LOTACAO' => 'Lotacao do usuario',
        ]]);
        $adodb->programarConsulta('identidades', $identidade);
        $adodb->programarConsulta('lock', [['ESCOPO' => 'ESTRATEGIA_ANUAL']]);
        $adodb->programarConsulta('estrategia_corrente', [[
            'CHAVE_CONFIGURACAO' => 'ESTRATEGIA_PUBLICACAO_PLANOS',
            'VALOR_CONFIGURACAO' => 'INDIVIDUAL',
            'DESCRICAO_CONFIGURACAO' => null,
        ]]);
        $adodb->programarConsulta('estrategia_adotada', [[
            'UNIDADE_ORCAMENTARIA' => '101',
            'ESTRATEGIA_ADOTADA' => 'INDIVIDUAL',
            'UASG_ADOTADA' => self::UASG,
        ]]);
        $adodb->programarConsulta('estrategia_adotada', [[
            'UNIDADE_ORCAMENTARIA' => '101',
            'ESTRATEGIA_ADOTADA' => 'INDIVIDUAL',
            'UASG_ADOTADA' => self::UASG,
        ]]);
        $adodb->programarConsulta('lock', [['ESCOPO' => 'UNIDADE_EXTERNA']]);
        $adodb->programarConsulta('identidades', $identidade);
        $adodb->programarConsulta('plano_vigente', [[
            'VERSAO' => '1',
            'DATA_HOMOLOGACAO' => '2026-08-20 14:35:12',
            'DATA_CONFIRMACAO' => $dataConfirmacao,
        ]]);
        $adodb->programarConsulta('demandas_vigentes', [[
            'ID_DEMANDA' => (string) $idDemanda,
            'SITUACAO' => '2',
            'ID_UNIDADE_REQUISITANTE' => '159',
            'DATA_DESEJADA' => '2027-05-31',
            'CATEGORIA' => '1',
            'DESCRICAO_DEMANDA' => 'Computador de teste',
            'UNIDADE_MEDIDA' => 'UN',
            'QUANTIDADE' => '1',
            'VALOR_TOTAL' => '1000.0000',
            'VALOR_ORCAMENTO_EXERCICIO' => '1000.0000',
            'CLASSIFICACAO_CATALOGO' => '1',
            'CODIGO_ITEM' => 'CATMAT-' . $idDemanda,
            'GRUPO' => '75',
            'DESCRICAO_GRUPO' => 'Equipamentos de teste',
            'ORIGEM' => 'N',
        ]]);
        $adodb->programarConsulta('estado_operacional', [[
            'ESTRATEGIA_ADOTADA_PUBLICACAO' => 'INDIVIDUAL',
            'UASG_ADOTADA' => self::UASG,
            'ESTADO_PUBLICACAO' => 'PUBLICADO',
            'SEQUENCIAL_PUBLICACAO_PNCP' => '77',
        ]]);

        if ($confirmacaoEsperada) {
            $adodb->programarConsulta('estado_operacional', [[
                'ESTRATEGIA_ADOTADA_PUBLICACAO' => 'INDIVIDUAL',
                'UASG_ADOTADA' => self::UASG,
                'ESTADO_PUBLICACAO' => 'PUBLICADO',
                'SEQUENCIAL_PUBLICACAO_PNCP' => '77',
            ]]);
        }

        if ($dataConfirmacao === null && $confirmacaoEsperada) {
            $adodb->programarConsulta('confirmacao_plano', [['DATA_CONFIRMACAO' => null]]);
            $adodb->programarConsulta('confirmacao_plano', [[
                'DATA_CONFIRMACAO' => '2026-08-31 10:00:00',
            ]]);
        }
    }

    private static function programarPncpJaConvergente(ExecutorCurlRoteirizado $curl): void
    {
        $baseAno = self::BASE_PNCP . '/v1/orgaos/' . self::CNPJ . '/pca/' . self::ANO;
        $identidade = [
            'cnpj' => self::CNPJ,
            'anoPca' => self::ANO,
            'codigoUnidade' => self::UASG,
            'sequencialPca' => 77,
        ];

        $curl->programar(
            'POST',
            self::BASE_PNCP . '/v1/usuarios/login',
            'login',
            200,
            ['Authorization: Bearer token-ficticio'],
            ''
        );
        $curl->programar(
            'GET',
            $baseAno . '/77/consolidado',
            'identidade',
            200,
            ['Content-Type: application/json'],
            json_encode($identidade, JSON_THROW_ON_ERROR)
        );
        $curl->programar(
            'GET',
            $baseAno . '/77/itens?pagina=1&tamanhoPagina=100',
            'itens',
            200,
            ['Content-Type: application/json'],
            json_encode([array_merge($identidade, ['numeroItem' => 1001])], JSON_THROW_ON_ERROR)
        );
    }

    private static function programarPncpRetificacao(ExecutorCurlRoteirizado $curl): void
    {
        $baseAno = self::BASE_PNCP . '/v1/orgaos/' . self::CNPJ . '/pca/' . self::ANO;
        $basePca = $baseAno . '/77';
        $identidade = [
            'cnpj' => self::CNPJ,
            'anoPca' => self::ANO,
            'codigoUnidade' => self::UASG,
            'sequencialPca' => 77,
        ];

        $curl->programar(
            'POST',
            self::BASE_PNCP . '/v1/usuarios/login',
            'login',
            200,
            ['Authorization: Bearer token-ficticio'],
            ''
        );

        foreach ([1001, 1002] as $indice => $numeroItem) {
            $curl->programar(
                'GET',
                $basePca . '/consolidado',
                'identidade',
                200,
                ['Content-Type: application/json'],
                json_encode($identidade, JSON_THROW_ON_ERROR)
            );
            $curl->programar(
                'GET',
                $basePca . '/itens?pagina=1&tamanhoPagina=100',
                'itens',
                200,
                ['Content-Type: application/json'],
                json_encode(
                    [array_merge($identidade, ['numeroItem' => $numeroItem])],
                    JSON_THROW_ON_ERROR
                )
            );

            if ($indice === 0) {
                $curl->programar(
                    'POST',
                    self::BASE_PNCP . '/v1/usuarios/login',
                    'login',
                    200,
                    ['Authorization: Bearer token-ficticio'],
                    ''
                );
                $curl->programar(
                    'DELETE',
                    $basePca . '/itens',
                    'excluir_itens',
                    200,
                    [],
                    ''
                );
                $curl->programar(
                    'POST',
                    $basePca . '/itens',
                    'inserir_itens',
                    201,
                    [],
                    ''
                );
            }
        }
    }

    private static function programarPncpRetificacaoInconclusiva(
        ExecutorCurlRoteirizado $curl
    ): void {
        $baseAno = self::BASE_PNCP . '/v1/orgaos/' . self::CNPJ . '/pca/' . self::ANO;
        $basePca = $baseAno . '/77';
        $identidade = [
            'cnpj' => self::CNPJ,
            'anoPca' => self::ANO,
            'codigoUnidade' => self::UASG,
            'sequencialPca' => 77,
        ];

        $curl->programar(
            'POST',
            self::BASE_PNCP . '/v1/usuarios/login',
            'login',
            200,
            ['Authorization: Bearer token-ficticio'],
            ''
        );
        $curl->programar(
            'GET',
            $basePca . '/consolidado',
            'identidade',
            200,
            ['Content-Type: application/json'],
            json_encode($identidade, JSON_THROW_ON_ERROR)
        );
        $curl->programar(
            'GET',
            $basePca . '/itens?pagina=1&tamanhoPagina=100',
            'itens',
            200,
            ['Content-Type: application/json'],
            json_encode(
                [array_merge($identidade, ['numeroItem' => 1001])],
                JSON_THROW_ON_ERROR
            )
        );
        $curl->programar(
            'POST',
            self::BASE_PNCP . '/v1/usuarios/login',
            'login',
            200,
            ['Authorization: Bearer token-ficticio'],
            ''
        );
        $curl->programar('DELETE', $basePca . '/itens', 'excluir_itens', 200, [], '');
        $curl->programar('POST', $basePca . '/itens', 'inserir_itens', 201, [], '');
        $curl->programar('GET', $basePca . '/consolidado', 'identidade', 503, [], '');
        $curl->programar('GET', $basePca . '/consolidado', 'identidade', 503, [], '');
    }

    private static function mapeamentoPlanos(): MapeamentoPlanosOracle
    {
        return new MapeamentoPlanosOracle(
            'SGI', 'PAC_PLANOS', 'ID_PLANO', 'FK_UNIDADE_ORCAMENTARIA',
            'ANO_EXERCICIO', 'VERSAO', 'DATA_HOMOLOGACAO', 'DATA_PUBLICACAO'
        );
    }

    private static function mapeamentoUnidades(): MapeamentoUnidadesOrcamentariasOracle
    {
        return new MapeamentoUnidadesOrcamentariasOracle(
            'SGI', 'PAC_UNIDADES_ORCAMENTARIAS',
            'ID_UNIDADE_ORCAMENTARIA', 'IDENTIDADE_UO',
            'CODIGO_UASG', 'DESCRICAO_UASG', 'ATIVA', 'S'
        );
    }

    private static function mapeamentoDemandas(): MapeamentoDemandasOracle
    {
        return new MapeamentoDemandasOracle(
            'SGI', 'PAC_DEMANDAS', 'ID_DEMANDA', 'FK_PLANO', 'SITUACAO',
            'FK_UNIDADE_REQUISITANTE', 'DATA_DESEJADA', 'CATEGORIA', 'DESCRICAO',
            'UNIDADE_MEDIDA', 'QUANTIDADE', 'VALOR_TOTAL', 'VALOR_ORCAMENTO_EXERCICIO',
            'CLASSIFICACAO_CATALOGO', 'CODIGO_ITEM', 'GRUPO_CODIGO',
            [
                'schema' => 'CEBISIS',
                'tabela' => 'MT_GRUPOS_MATERIAIS',
                'dblink' => 'READ_ORCL',
                'colunaCodigo' => 'CODIGO',
                'colunaDescricao' => 'DESCRICAO',
            ],
            'ORIGEM'
        );
    }
}
