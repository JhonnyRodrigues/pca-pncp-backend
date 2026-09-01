<?php

declare(strict_types=1);

namespace Tests\Composition;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Semae\Pca\Application\ValueObjects\CorrelacaoExecucao;
use Semae\Pca\Composition\CompositionRootPublicacaoPca;
use Semae\Pca\Presentation\Facades\FachadaPublicarPca;
use stdClass;

final class CompositionRootPublicacaoPcaTest extends TestCase
{
    public function testDeveCriarGrafoNovoSemExecutarIoOuFecharConexao(): void
    {
        $adodb = new AdodbSemIoCompositionRoot();
        $root = new CompositionRootPublicacaoPca();
        $perfil = $this->perfilValido();
        $perfilProducao = $perfil;
        $perfilProducao['ambiente'] = 'PRODUCAO';
        $perfilProducao['pncp']['ca_bundle'] = null;

        $primeira = $root->montar(
            $adodb,
            'usuario.teste',
            $perfilProducao,
            new CorrelacaoExecucao('550E8400-E29B-41D4-A716-446655440000')
        );
        $segunda = $root->montar(
            $adodb,
            'usuario.teste',
            $perfil,
            new CorrelacaoExecucao('6BA7B810-9DAD-41D1-80B4-00C04FD430C8')
        );

        $this->assertInstanceOf(FachadaPublicarPca::class, $primeira);
        $this->assertInstanceOf(FachadaPublicarPca::class, $segunda);
        $this->assertNotSame($primeira, $segunda);
        $this->assertSame(0, $adodb->chamadas);
        $this->assertFalse($adodb->fechada);
    }

    public function testDeveExigirTodasAsChavesSemAplicarDefaults(): void
    {
        $perfil = $this->perfilValido();

        foreach (array_keys($perfil) as $chave) {
            $incompleto = $perfil;
            unset($incompleto[$chave]);
            $this->assertPerfilInvalido($incompleto);
        }

        foreach (array_keys($perfil['pncp']) as $chave) {
            $incompleto = $perfil;
            unset($incompleto['pncp'][$chave]);
            $this->assertPerfilInvalido($incompleto);
        }

        foreach (array_keys($perfil['oracle']) as $chave) {
            $incompleto = $perfil;
            unset($incompleto['oracle'][$chave]);
            $this->assertPerfilInvalido($incompleto);
        }

        foreach (array_keys($perfil['oracle']['mapeamentos']) as $chave) {
            $incompleto = $perfil;
            unset($incompleto['oracle']['mapeamentos'][$chave]);
            $this->assertPerfilInvalido($incompleto);
        }

        foreach ($perfil['oracle']['mapeamentos'] as $grupo => $mapeamento) {
            foreach (array_keys($mapeamento) as $campo) {
                $incompleto = $perfil;
                unset($incompleto['oracle']['mapeamentos'][$grupo][$campo]);
                $this->assertPerfilInvalido($incompleto);
            }
        }
    }

    public function testDeveRejeitarEstruturasValoresEUrlConfiguravel(): void
    {
        $perfil = $this->perfilValido();
        $casos = [];

        $casos['ambiente desconhecido'] = array_replace($perfil, [
            'ambiente' => 'homologacao',
        ]);
        $casos['CNPJ com tipo invalido'] = array_replace($perfil, [
            'cnpj' => 12345678000195,
        ]);
        $casos['chave superior desconhecida'] = array_replace($perfil, [
            'perfil_padrao' => true,
        ]);

        $casos['URL configuravel'] = $perfil;
        $casos['URL configuravel']['pncp']['base_url'] = 'https://destino.example';
        $casos['credencial vazia'] = $perfil;
        $casos['credencial vazia']['pncp']['login'] = '   ';
        $casos['numero como texto'] = $perfil;
        $casos['numero como texto']['pncp']['timeout_login_segundos'] = '30';
        $casos['retry incoerente'] = $perfil;
        $casos['retry incoerente']['pncp']['espera_retry_padrao_ms'] = 6_000;
        $casos['CA bundle relativo'] = $perfil;
        $casos['CA bundle relativo']['pncp']['ca_bundle'] = 'cacert.pem';
        $casos['CA bundle ausente'] = $perfil;
        $casos['CA bundle ausente']['pncp']['ca_bundle'] =
            __DIR__ . DIRECTORY_SEPARATOR . 'cacert-inexistente.pem';
        $casos['timezone invalido'] = $perfil;
        $casos['timezone invalido']['oracle']['timezone'] = 'Fuso/Inexistente';
        $casos['mapeamento nao array'] = $perfil;
        $casos['mapeamento nao array']['oracle']['mapeamentos']['planos'] = 'PAC_PLANOS';
        $casos['mapeamento incompleto'] = $perfil;
        unset($casos['mapeamento incompleto']['oracle']['mapeamentos']['historicos']['colunaLotacao']);
        $casos['mapeamento com alias adicional'] = $perfil;
        $casos['mapeamento com alias adicional']['oracle']['mapeamentos']['historicos']['alias'] =
            'IGNORAR';
        $casos['mapeamento Oracle inseguro'] = $perfil;
        $casos['mapeamento Oracle inseguro']['oracle']['mapeamentos']['planos']['tabela'] =
            'PAC_PLANOS; DROP TABLE X';

        foreach ($casos as $caso) {
            $this->assertPerfilInvalido($caso);
        }
    }

    public function testDeveValidarPerfilIntegralAntesDaConexaoEmprestada(): void
    {
        $perfil = $this->perfilValido();
        $perfil['ambiente'] = 'DESCONHECIDO';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O perfil de publicação é inválido.');

        (new CompositionRootPublicacaoPca())->montar(
            new stdClass(),
            'usuario.teste',
            $perfil,
            new CorrelacaoExecucao('550e8400-e29b-41d4-a716-446655440000')
        );
    }

    public function testDeveRejeitarLoginAusenteDepoisDeValidarOPerfil(): void
    {
        $adodb = new AdodbSemIoCompositionRoot();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O login autenticado é obrigatório.');

        (new CompositionRootPublicacaoPca())->montar(
            $adodb,
            '   ',
            $this->perfilValido(),
            new CorrelacaoExecucao('550e8400-e29b-41d4-a716-446655440000')
        );
    }

    /** @param array<string, mixed> $perfil */
    private function assertPerfilInvalido(array $perfil): void
    {
        try {
            (new CompositionRootPublicacaoPca())->montar(
                new stdClass(),
                'usuario.teste',
                $perfil,
                new CorrelacaoExecucao('550e8400-e29b-41d4-a716-446655440000')
            );
            $this->fail('Era esperado perfil inválido.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('O perfil de publicação é inválido.', $exception->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function perfilValido(): array
    {
        return [
            'ambiente' => 'HOMOLOGACAO',
            'cnpj' => '12345678000195',
            'pncp' => [
                'login' => 'login-ficticio',
                'senha' => 'senha-ficticia',
                'ca_bundle' => __FILE__,
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
                'mapeamentos' => [
                    'controles_concorrencia' => [
                        'schema' => 'SGI',
                        'tabela' => 'PAC_CONTROLES_CONCORRENCIA',
                        'colunaEscopo' => 'ESCOPO',
                        'colunaAnoExecucao' => 'ANO_EXECUCAO',
                        'colunaIdentificadorAlvo' => 'IDENTIFICADOR_ALVO',
                        'identificadorAlvoEstrategiaAnual' => 'ESTRATEGIA_ANUAL',
                    ],
                    'configuracao_estrategia' => [
                        'schema' => 'SGI',
                        'tabela' => 'PAC_CONFIGURACOES',
                        'colunaChave' => 'CHAVE',
                        'colunaValor' => 'VALOR',
                        'colunaDescricao' => 'DESCRICAO',
                        'chaveEstrategia' => 'ESTRATEGIA_PUBLICACAO_PLANOS',
                        'chaveUasgConsolidadora' => 'UASG_CONSOLIDADORA',
                        'literalEstrategiaIndividual' => 'INDIVIDUAL',
                        'literalEstrategiaConsolidada' => 'CONSOLIDADO',
                    ],
                    'planos_estrategia' => [
                        'schema' => 'SGI',
                        'tabela' => 'PAC_PLANOS',
                        'colunaAnoExecucao' => 'ANO_EXERCICIO',
                        'colunaUnidadeOrcamentaria' => 'FK_UNIDADE_ORCAMENTARIA',
                        'colunaEstrategiaAdotada' => 'ESTRATEGIA_ADOTADA_PUBLICACAO',
                        'colunaUasgAdotada' => 'UASG_ADOTADA',
                        'literalEstrategiaIndividual' => 'INDIVIDUAL',
                        'literalEstrategiaConsolidada' => 'CONSOLIDADO',
                    ],
                    'planos' => [
                        'schema' => 'SGI',
                        'tabela' => 'PAC_PLANOS',
                        'colunaId' => 'ID_PLANO',
                        'colunaUnidadeOrcamentaria' => 'FK_UNIDADE_ORCAMENTARIA',
                        'colunaAnoExecucao' => 'ANO_EXERCICIO',
                        'colunaVersao' => 'VERSAO',
                        'colunaDataHomologacao' => 'DATA_HOMOLOGACAO',
                        'colunaDataConfirmacao' => 'DATA_PUBLICACAO',
                    ],
                    'demandas' => [
                        'schema' => 'SGI',
                        'tabela' => 'PAC_DEMANDAS',
                        'colunaId' => 'ID_DEMANDA',
                        'colunaPlanoOrigem' => 'FK_PLANO',
                        'colunaSituacao' => 'SITUACAO',
                        'colunaUnidadeRequisitante' => 'FK_UNIDADE_REQUISITANTE',
                        'colunaDataDesejada' => 'DATA_DESEJADA',
                        'colunaCategoria' => 'CATEGORIA',
                        'colunaDescricao' => 'DESCRICAO',
                        'colunaUnidadeMedida' => 'UNIDADE_MEDIDA',
                        'colunaQuantidade' => 'QUANTIDADE',
                        'colunaValorTotal' => 'VALOR_TOTAL',
                        'colunaValorOrcamentoExercicio' => 'VALOR_ORCAMENTO_EXERCICIO',
                        'colunaCatalogo' => 'CATALOGO',
                        'colunaCodigoItem' => 'CODIGO_ITEM',
                        'colunaGrupo' => 'GRUPO_CODIGO',
                        'grupoRemoto' => [
                            'schema' => 'CEBISIS',
                            'tabela' => 'MT_GRUPOS_MATERIAIS',
                            'dblink' => 'READ_ORCL',
                            'colunaCodigo' => 'CODIGO',
                            'colunaDescricao' => 'DESCRICAO',
                        ],
                        'colunaOrigem' => 'ORIGEM',
                    ],
                    'unidades_orcamentarias' => [
                        'schema' => 'SGI',
                        'tabela' => 'PAC_UNIDADES_ORCAMENTARIAS',
                        'colunaRelacaoPlano' => 'ID_UNIDADE_ORCAMENTARIA',
                        'colunaIdentidade' => 'IDENTIDADE_UO',
                        'colunaCodigoUasg' => 'CODIGO_UASG',
                        'colunaDescricaoUasg' => 'DESCRICAO_UASG',
                        'colunaAtivo' => 'ATIVA',
                        'valorAtivo' => 'S',
                    ],
                    'lotacoes' => [
                        'schema' => 'SGI',
                        'tabela' => 'RH_LOTACOES',
                        'colunaId' => 'COD_LOTACAO',
                        'colunaDescricao' => 'NOME_LOTACAO',
                    ],
                    'estado_operacional' => [
                        'schema' => 'SGI',
                        'tabela' => 'PAC_PLANOS',
                        'colunaUnidadeOrcamentaria' => 'FK_UNIDADE_ORCAMENTARIA',
                        'colunaAnoExecucao' => 'ANO_EXERCICIO',
                        'colunaEstrategia' => 'ESTRATEGIA_ADOTADA_PUBLICACAO',
                        'colunaUasg' => 'UASG_ADOTADA',
                        'colunaEstadoPublicacao' => 'ESTADO_PUBLICACAO',
                        'colunaSequencialPublicacaoPncp' => 'SEQUENCIAL_PUBLICACAO_PNCP',
                        'literalEstrategiaIndividual' => 'INDIVIDUAL',
                        'literalEstrategiaConsolidada' => 'CONSOLIDADO',
                        'literalNaoPublicado' => 'NAO_PUBLICADO',
                        'literalPublicado' => 'PUBLICADO',
                        'literalExcluido' => 'EXCLUIDO',
                    ],
                    'representantes' => [
                        'schema' => 'SGI',
                        'tabela' => 'RH_FUNCIONARIOS',
                        'colunaLogin' => 'NOME_USUARIO',
                        'colunaId' => 'COD_FUNCIONARIO',
                        'colunaNome' => 'NOME_CIVIL',
                        'colunaAtivo' => 'ATIVO',
                        'valorAtivo' => 1,
                    ],
                    'lotacoes_usuario' => [
                        'schema' => 'SGI',
                        'tabelaVinculos' => 'RH_FUNCIONARIOS_LOTACOES',
                        'tabelaLotacoes' => 'RH_LOTACOES',
                        'colunaFuncionarioVinculo' => 'COD_FUNCIONARIO',
                        'colunaLotacaoVinculo' => 'COD_LOTACAO',
                        'colunaFimVinculo' => 'DT_FIM_LOTACAO',
                        'colunaIdLotacao' => 'COD_LOTACAO',
                        'colunaNomeLotacao' => 'NOME_LOTACAO',
                        'colunaAtivoLotacao' => 'ATIVO',
                        'valorAtivo' => 1,
                    ],
                    'historicos' => [
                        'schema' => 'SGI',
                        'tabela' => 'PAC_HISTORICOS',
                        'colunaInstante' => 'DATA_REGISTRO',
                        'colunaProcedimento' => 'PROCEDIMENTO',
                        'colunaMensagem' => 'MENSAGEM',
                        'colunaRepresentante' => 'FK_REPRESENTANTE',
                        'colunaLotacao' => 'FK_LOTACAO',
                    ],
                ],
            ],
        ];
    }
}

final class AdodbSemIoCompositionRoot
{
    public int $chamadas = 0;
    public bool $fechada = false;

    public function BeginTrans(): bool
    {
        $this->chamadas++;

        return true;
    }

    public function CommitTrans(): bool
    {
        $this->chamadas++;

        return true;
    }

    public function RollbackTrans(): bool
    {
        $this->chamadas++;

        return true;
    }

    /** @param mixed[] $parametros */
    public function Execute(string $sql, array $parametros = []): bool
    {
        $this->chamadas++;

        return true;
    }

    /** @param mixed[] $parametros @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $parametros = []): array
    {
        $this->chamadas++;

        return [];
    }

    public function Param(string $nome): string
    {
        $this->chamadas++;

        return ':' . $nome;
    }

    public function ErrorNo(): int
    {
        $this->chamadas++;

        return 0;
    }

    public function ErrorMsg(): string
    {
        $this->chamadas++;

        return '';
    }

    public function Close(): void
    {
        $this->chamadas++;
        $this->fechada = true;
    }
}
