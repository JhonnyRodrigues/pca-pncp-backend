<?php

declare(strict_types=1);

namespace Tests\Integration\Sprint84\Support;

use Semae\Pca\Application\Results\PublicarPcaResponse;
use Semae\Pca\Domain\Enums\DecisaoProcessamentoPlano;
use Tests\Integration\Composition\Support\AdodbControlado;
use Tests\Integration\Composition\Support\CenarioIntegracaoNucleo;
use Tests\Integration\Composition\Support\ExecutorCurlRoteirizado;
use Tests\Integration\Composition\Support\RegistroCronologico;

final class CenarioMatrizSprint84
{
    private const ANO = 2027;
    private const CNPJ = '12345678000195';
    private const BASE_PNCP = 'https://treina.pncp.gov.br/api/pncp';
    private const UASG_CONSOLIDADORA = '986700';

    /** @var array<int, string> */
    private const UASGS = [101 => '928341', 202 => '928342'];

    /** @var array<int, int> */
    private const SEQUENCIAIS = [101 => 71, 202 => 72];

    private function __construct(
        private readonly string $modo,
        private readonly DecisaoProcessamentoPlano $decisao,
        private readonly CenarioIntegracaoNucleo $nucleo,
        public readonly RegistroCronologico $eventos,
        public readonly AdodbControlado $adodb,
        public readonly ExecutorCurlRoteirizado $curl
    ) {}

    public static function criar(
        string $modo,
        DecisaoProcessamentoPlano $decisao,
        bool $projecaoPendente = false
    ): self {
        $eventos = new RegistroCronologico();
        $adodb = new AdodbControlado($eventos);
        $curl = new ExecutorCurlRoteirizado($eventos);

        self::programarOracle($adodb, $modo, $decisao, $projecaoPendente);
        self::programarPncp($curl, $modo, $decisao);

        return new self(
            $modo,
            $decisao,
            CenarioIntegracaoNucleo::controlado($eventos, $adodb, $curl),
            $eventos,
            $adodb,
            $curl
        );
    }

    public function executar(): PublicarPcaResponse
    {
        return $this->nucleo->executar();
    }

    /** @return string[] */
    public function rotasEscritaEsperadas(): array
    {
        $quantidade = $this->modo === 'INDIVIDUAL' ? 2 : 1;

        return match ($this->decisao) {
            DecisaoProcessamentoPlano::PUBLICAR => array_fill(0, $quantidade, 'publicar'),
            DecisaoProcessamentoPlano::NENHUMA_ACAO => [],
            DecisaoProcessamentoPlano::EXCLUIR_PCA => array_fill(
                0,
                $quantidade,
                'excluir_pca'
            ),
            DecisaoProcessamentoPlano::RETIFICAR => array_merge(...array_fill(
                0,
                $quantidade,
                ['excluir_itens', 'inserir_itens']
            )),
        };
    }

    private static function programarOracle(
        AdodbControlado $adodb,
        string $modo,
        DecisaoProcessamentoPlano $decisao,
        bool $projecaoPendente
    ): void {
        $identidades = self::identidades();
        $adodb->programarConsulta('contexto_representante', [[
            'ID_REPRESENTANTE' => '709',
            'NOME_REPRESENTANTE' => 'Representante da matriz',
        ]]);
        $adodb->programarConsulta('contexto_lotacao', [[
            'ID_LOTACAO' => '159',
            'NOME_LOTACAO' => 'Lotação da matriz',
        ]]);
        $adodb->programarConsulta('identidades', $identidades);
        $adodb->programarConsulta('lock', [['ESCOPO' => 'ESTRATEGIA_ANUAL']]);
        $adodb->programarConsulta('estrategia_corrente', self::estrategiaCorrente($modo));
        self::programarAdocaoEstrategia($adodb, $modo, $decisao);

        if ($modo === 'INDIVIDUAL') {
            foreach (array_keys(self::UASGS) as $uo) {
                self::programarUnidadeOracle($adodb, $modo, $decisao, [$uo], $projecaoPendente);
            }
            return;
        }

        self::programarUnidadeOracle(
            $adodb,
            $modo,
            $decisao,
            array_keys(self::UASGS),
            $projecaoPendente
        );
    }

    private static function programarAdocaoEstrategia(
        AdodbControlado $adodb,
        string $modo,
        DecisaoProcessamentoPlano $decisao
    ): void {
        $antes = self::linhasEstrategia($modo, $decisao, false);
        $depois = self::linhasEstrategia($modo, $decisao, true);

        $adodb->programarConsulta('estrategia_adotada', $antes);
        $adodb->programarConsulta('estrategia_adotada', $antes);

        if ($antes === $depois) {
            return;
        }

        if ($modo === 'INDIVIDUAL') {
            $adodb->programarConsulta('uasgs_individuais', array_map(
                static fn (int $uo, string $uasg): array => [
                    'UNIDADE_ORCAMENTARIA' => (string) $uo,
                    'CODIGO_UASG' => $uasg,
                    'DESCRICAO_UASG' => 'UASG ' . $uo,
                ],
                array_keys(self::UASGS),
                array_values(self::UASGS)
            ));
        }

        $adodb->programarConsulta('estrategia_adotada', $depois);
    }

    /** @param int[] $uos */
    private static function programarUnidadeOracle(
        AdodbControlado $adodb,
        string $modo,
        DecisaoProcessamentoPlano $decisao,
        array $uos,
        bool $projecaoPendente
    ): void {
        $adodb->programarConsulta('lock', [['ESCOPO' => 'UNIDADE_EXTERNA']]);
        $adodb->programarConsulta('identidades', self::identidades());

        foreach ($uos as $uo) {
            $adodb->programarConsulta('plano_vigente', [[
                'VERSAO' => in_array(
                    $decisao,
                    [DecisaoProcessamentoPlano::RETIFICAR, DecisaoProcessamentoPlano::EXCLUIR_PCA],
                    true
                ) || $projecaoPendente ? '2' : '1',
                'DATA_HOMOLOGACAO' => '2026-09-08 10:00:00',
                'DATA_CONFIRMACAO' => $decisao === DecisaoProcessamentoPlano::NENHUMA_ACAO
                    ? '2026-09-08 11:00:00'
                    : null,
            ]]);
            $adodb->programarConsulta(
                'demandas_vigentes',
                self::demandasLocais($uo, $decisao)
            );
        }

        $adodb->programarConsulta(
            'estado_operacional',
            self::estadoOracle($modo, $decisao, $uos, false, $projecaoPendente)
        );

        $adodb->programarConsulta(
            'estado_operacional',
            self::estadoOracle($modo, $decisao, $uos, false, $projecaoPendente)
        );

        if ($decisao !== DecisaoProcessamentoPlano::NENHUMA_ACAO || $projecaoPendente) {
            $adodb->programarConsulta(
                'estado_operacional',
                self::estadoOracle($modo, $decisao, $uos, true, $projecaoPendente)
            );
        }

        if ($decisao === DecisaoProcessamentoPlano::NENHUMA_ACAO) {
            return;
        }

        foreach ($uos as $_) {
            $adodb->programarConsulta('confirmacao_plano', [['DATA_CONFIRMACAO' => null]]);
            $adodb->programarConsulta('confirmacao_plano', [[
                'DATA_CONFIRMACAO' => '2026-09-08 12:00:00',
            ]]);
        }
    }

    private static function programarPncp(
        ExecutorCurlRoteirizado $curl,
        string $modo,
        DecisaoProcessamentoPlano $decisao
    ): void {
        $curl->programar(
            'POST',
            self::BASE_PNCP . '/v1/usuarios/login',
            'login',
            200,
            ['Authorization: Bearer token-matriz'],
            ''
        );

        if ($modo === 'INDIVIDUAL') {
            foreach (array_keys(self::UASGS) as $uo) {
                self::programarUnidadePncp(
                    $curl,
                    self::UASGS[$uo],
                    self::SEQUENCIAIS[$uo],
                    self::idsLocais($uo, $decisao),
                    self::idsExternosIniciais($uo, $decisao),
                    $decisao
                );
            }
            return;
        }

        $idsLocais = [];
        $idsExternos = [];
        foreach (array_keys(self::UASGS) as $uo) {
            $idsLocais = [...$idsLocais, ...self::idsLocais($uo, $decisao)];
            $idsExternos = [
                ...$idsExternos,
                ...self::idsExternosIniciais($uo, $decisao),
            ];
        }
        self::programarUnidadePncp(
            $curl,
            self::UASG_CONSOLIDADORA,
            81,
            $idsLocais,
            $idsExternos,
            $decisao
        );
    }

    /** @param int[] $idsLocais @param int[] $idsExternos */
    private static function programarUnidadePncp(
        ExecutorCurlRoteirizado $curl,
        string $uasg,
        int $sequencial,
        array $idsLocais,
        array $idsExternos,
        DecisaoProcessamentoPlano $decisao
    ): void {
        if ($decisao === DecisaoProcessamentoPlano::PUBLICAR) {
            self::programarListaVazia($curl);
            $curl->programar(
                'POST',
                self::BASE_PNCP . '/v1/orgaos/' . self::CNPJ . '/pca',
                'publicar',
                201,
                ['Location: /v1/orgaos/' . self::CNPJ . '/pca/' . self::ANO . '/' . $sequencial],
                ''
            );
            self::programarPcaEncontrado($curl, $uasg, $sequencial, $idsLocais);
            return;
        }

        self::programarPcaEncontrado($curl, $uasg, $sequencial, $idsExternos);

        if ($decisao === DecisaoProcessamentoPlano::NENHUMA_ACAO) {
            return;
        }

        $basePca = self::basePca($sequencial);
        if ($decisao === DecisaoProcessamentoPlano::RETIFICAR) {
            $curl->programar('DELETE', $basePca . '/itens', 'excluir_itens', 200, [], '');
            $curl->programar('POST', $basePca . '/itens', 'inserir_itens', 201, [], '');
            self::programarPcaEncontrado($curl, $uasg, $sequencial, $idsLocais);
            return;
        }

        $curl->programar('DELETE', $basePca, 'excluir_pca', 200, [], '');
        $curl->programar('GET', $basePca . '/consolidado', 'identidade', 400, [], '');
        self::programarListaVazia($curl);
    }

    /** @param int[] $ids */
    private static function programarPcaEncontrado(
        ExecutorCurlRoteirizado $curl,
        string $uasg,
        int $sequencial,
        array $ids
    ): void {
        $identidade = [
            'cnpj' => self::CNPJ,
            'anoPca' => self::ANO,
            'codigoUnidade' => $uasg,
            'sequencialPca' => $sequencial,
        ];
        $curl->programar(
            'GET',
            self::basePca($sequencial) . '/consolidado',
            'identidade',
            200,
            ['Content-Type: application/json'],
            json_encode($identidade, JSON_THROW_ON_ERROR)
        );
        $curl->programar(
            'GET',
            self::basePca($sequencial) . '/itens?pagina=1&tamanhoPagina=100',
            'itens',
            200,
            ['Content-Type: application/json'],
            json_encode(array_map(
                static fn (int $id): array => array_merge(
                    $identidade,
                    ['numeroItem' => $id]
                ),
                $ids
            ), JSON_THROW_ON_ERROR)
        );
    }

    private static function programarListaVazia(ExecutorCurlRoteirizado $curl): void
    {
        $curl->programar(
            'GET',
            self::BASE_PNCP . '/v1/orgaos/' . self::CNPJ . '/pca/' . self::ANO
                . '/consolidado/unidades?pagina=1&tamanhoPagina=100',
            'listar_unidades',
            200,
            ['Content-Type: application/json'],
            '[]'
        );
    }

    /** @return array<int, array<string, mixed>> */
    private static function identidades(): array
    {
        return array_map(
            static fn (int $uo, string $uasg): array => [
                'CHAVE_UO_PLANO' => (string) $uo,
                'IDENTIDADE_UO' => 'UO-' . $uo,
                'ID_LOTACAO' => (string) $uo,
                'DESCRICAO_LOTACAO' => 'Lotação ' . $uo,
                'DESCRICAO_UO' => 'Unidade Orçamentária ' . $uo,
                'CODIGO_UASG' => $uasg,
                'DESCRICAO_UASG' => 'UASG ' . $uo,
            ],
            array_keys(self::UASGS),
            array_values(self::UASGS)
        );
    }

    /** @return array<int, array<string, mixed>> */
    private static function estrategiaCorrente(string $modo): array
    {
        $linhas = [[
            'CHAVE_CONFIGURACAO' => 'ESTRATEGIA_PUBLICACAO_PLANOS',
            'VALOR_CONFIGURACAO' => $modo,
            'DESCRICAO_CONFIGURACAO' => null,
        ]];

        if ($modo === 'CONSOLIDADO') {
            $linhas[] = [
                'CHAVE_CONFIGURACAO' => 'UASG_CONSOLIDADORA',
                'VALOR_CONFIGURACAO' => self::UASG_CONSOLIDADORA,
                'DESCRICAO_CONFIGURACAO' => 'UASG Consolidadora',
            ];
        }

        return $linhas;
    }

    /** @return array<int, array<string, mixed>> */
    private static function linhasEstrategia(
        string $modo,
        DecisaoProcessamentoPlano $decisao,
        bool $aposAdocao
    ): array {
        $linhas = [];
        $possuiNovaVersao = in_array(
            $decisao,
            [DecisaoProcessamentoPlano::RETIFICAR, DecisaoProcessamentoPlano::EXCLUIR_PCA],
            true
        );

        foreach (self::UASGS as $uo => $uasgIndividual) {
            $uasg = $modo === 'INDIVIDUAL' ? $uasgIndividual : self::UASG_CONSOLIDADORA;
            $adotada = $decisao !== DecisaoProcessamentoPlano::PUBLICAR || $aposAdocao;
            $linhas[] = self::linhaEstrategia($uo, $adotada ? $modo : null, $adotada ? $uasg : null);

            if ($possuiNovaVersao) {
                $linhas[] = self::linhaEstrategia(
                    $uo,
                    $aposAdocao ? $modo : null,
                    $aposAdocao ? $uasg : null
                );
            }
        }

        return $linhas;
    }

    /** @return array<string, mixed> */
    private static function linhaEstrategia(int $uo, ?string $modo, ?string $uasg): array
    {
        return [
            'UNIDADE_ORCAMENTARIA' => (string) $uo,
            'ESTRATEGIA_ADOTADA' => $modo,
            'UASG_ADOTADA' => $uasg,
        ];
    }

    /** @param int[] $uos @return array<int, array<string, mixed>> */
    private static function estadoOracle(
        string $modo,
        DecisaoProcessamentoPlano $decisao,
        array $uos,
        bool $aposRegistro,
        bool $projecaoPendente = false
    ): array {
        $linhas = [];
        foreach ($uos as $uo) {
            $uasg = $modo === 'INDIVIDUAL' ? self::UASGS[$uo] : self::UASG_CONSOLIDADORA;
            $sequencial = $modo === 'INDIVIDUAL' ? self::SEQUENCIAIS[$uo] : 81;
            $estado = match ($decisao) {
                DecisaoProcessamentoPlano::PUBLICAR => $aposRegistro ? 'PUBLICADO' : null,
                DecisaoProcessamentoPlano::EXCLUIR_PCA => $aposRegistro ? 'EXCLUIDO' : 'PUBLICADO',
                DecisaoProcessamentoPlano::RETIFICAR,
                DecisaoProcessamentoPlano::NENHUMA_ACAO => 'PUBLICADO',
            };
            $linhas[] = self::linhaEstado($modo, $uasg, $estado, $estado === null ? null : $sequencial);

            if ($projecaoPendente || in_array(
                $decisao,
                [DecisaoProcessamentoPlano::RETIFICAR, DecisaoProcessamentoPlano::EXCLUIR_PCA],
                true
            )) {
                $linhas[] = self::linhaEstado(
                    $modo,
                    $uasg,
                    $aposRegistro ? $estado : null,
                    $aposRegistro ? $sequencial : null
                );
            }
        }

        return $linhas;
    }

    /** @return array<string, mixed> */
    private static function linhaEstado(
        string $modo,
        string $uasg,
        ?string $estado,
        ?int $sequencial
    ): array {
        return [
            'ESTRATEGIA_ADOTADA_PUBLICACAO' => $modo,
            'UASG_ADOTADA' => $uasg,
            'ESTADO_PUBLICACAO' => $estado,
            'SEQUENCIAL_PUBLICACAO_PNCP' => $sequencial,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function demandasLocais(
        int $uo,
        DecisaoProcessamentoPlano $decisao
    ): array {
        return array_map(
            static fn (int $id): array => [
                'ID_DEMANDA' => (string) $id,
                'SITUACAO' => '2',
                'ID_UNIDADE_REQUISITANTE' => '159',
                'DATA_DESEJADA' => '2027-05-31',
                'CATEGORIA' => '1',
                'DESCRICAO_DEMANDA' => 'Demanda ' . $id,
                'UNIDADE_MEDIDA' => 'UN',
                'QUANTIDADE' => '1',
                'VALOR_TOTAL' => '1000.0000',
                'VALOR_ORCAMENTO_EXERCICIO' => '1000.0000',
                'CLASSIFICACAO_CATALOGO' => '1',
                'CODIGO_ITEM' => 'CATMAT-' . $id,
                'GRUPO' => '75',
                'DESCRICAO_GRUPO' => 'Equipamentos',
                'ORIGEM' => 'N',
            ],
            self::idsLocais($uo, $decisao)
        );
    }

    /** @return int[] */
    private static function idsLocais(int $uo, DecisaoProcessamentoPlano $decisao): array
    {
        return match ($decisao) {
            DecisaoProcessamentoPlano::EXCLUIR_PCA => [],
            DecisaoProcessamentoPlano::RETIFICAR => [$uo * 10 + 2],
            DecisaoProcessamentoPlano::PUBLICAR,
            DecisaoProcessamentoPlano::NENHUMA_ACAO => [$uo * 10 + 1],
        };
    }

    /** @return int[] */
    private static function idsExternosIniciais(
        int $uo,
        DecisaoProcessamentoPlano $decisao
    ): array {
        return match ($decisao) {
            DecisaoProcessamentoPlano::PUBLICAR => [],
            DecisaoProcessamentoPlano::RETIFICAR,
            DecisaoProcessamentoPlano::EXCLUIR_PCA => [$uo * 10],
            DecisaoProcessamentoPlano::NENHUMA_ACAO => self::idsLocais($uo, $decisao),
        };
    }

    private static function basePca(int $sequencial): string
    {
        return self::BASE_PNCP . '/v1/orgaos/' . self::CNPJ . '/pca/'
            . self::ANO . '/' . $sequencial;
    }
}
