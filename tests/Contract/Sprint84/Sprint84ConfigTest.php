<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Tests\Contract\Sprint84\Support\Sprint84Config;
use Tests\Contract\Sprint84\Support\Sprint84ConfigurationException;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class Sprint84ConfigTest extends TestCase
{
    private const VARIAVEL_CONFIGURACAO = 'PCA_SPRINT84_CONFIG_FILE';
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';

    private string|false $configuracaoAnterior;
    private string|false $registroAnterior;
    private string $diretorioTemporario;

    protected function setUp(): void
    {
        $this->configuracaoAnterior = getenv(self::VARIAVEL_CONFIGURACAO);
        $this->registroAnterior = getenv(self::VARIAVEL_REGISTRO);
        $base = tempnam(sys_get_temp_dir(), 'pca-s84-config-');

        if ($base === false || !unlink($base) || !mkdir($base)) {
            throw new RuntimeException('Não foi possível preparar o diretório temporário.');
        }

        $this->diretorioTemporario = $base;
    }

    protected function tearDown(): void
    {
        $this->restaurarVariavel(self::VARIAVEL_CONFIGURACAO, $this->configuracaoAnterior);
        $this->restaurarVariavel(self::VARIAVEL_REGISTRO, $this->registroAnterior);

        foreach (glob($this->diretorioTemporario . DIRECTORY_SEPARATOR . '*') ?: [] as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }

        rmdir($this->diretorioTemporario);
    }

    public function testDeveCarregarConfiguracaoExataEPerfilFinalDeHomologacao(): void
    {
        $this->configurarPerfil(PerfilPublicacaoPcaFixture::valido());
        $configuracao = $this->configuracaoValida();
        putenv(
            self::VARIAVEL_CONFIGURACAO . '='
            . $this->escrever('config.php', $configuracao)
        );

        $carregada = Sprint84Config::loadFromEnvironment();
        $perfil = Sprint84Config::loadHomologationProfile(
            $carregada,
            $carregada['server_name']
        );
        $perfilBruto = Sprint84Config::loadHomologationProfileArray(
            $carregada,
            $carregada['server_name']
        );

        $this->assertSame($configuracao, $carregada);
        $this->assertSame(AmbientePncp::HOMOLOGACAO, $perfil->getAmbiente());
        $this->assertSame('12345678000195', $perfilBruto['cnpj']);
        $this->assertSame('login-ficticio', $perfilBruto['pncp']['login']);
    }

    public function testPrecheckDeveAceitarAusenciaOuPcaEncontradoComoEstadoEsperado(): void
    {
        foreach (['AUSENTE', 'ENCONTRADO'] as $estado) {
            $configuracao = $this->configuracaoValida('PRECHECK');
            $configuracao['estado_pncp_esperado'] = $estado;

            $this->assertSame($configuracao, Sprint84Config::validate($configuracao));
        }
    }

    public function testPrecheckDeveAceitarFotografiaVaziaSemPermissoesDeEscrita(): void
    {
        $configuracao = $this->configuracaoValida('PRECHECK');
        $configuracao['allow_oracle_writes'] = false;
        $configuracao['allow_pncp_writes'] = false;
        $configuracao['estado_pncp_esperado'] = 'ENCONTRADO';
        $configuracao['unidades'][0]['quantidade_fotografia'] = 0;
        $configuracao['unidades'][1]['quantidade_fotografia'] = 0;

        $this->assertSame($configuracao, Sprint84Config::validate($configuracao));
    }

    public function testPublicarENenhumaAcaoDevemContinuarExigindoFotografiaPositiva(): void
    {
        foreach (['PUBLICAR', 'NENHUMA_ACAO'] as $etapa) {
            $configuracao = $this->configuracaoValida($etapa);
            $configuracao['unidades'][0]['quantidade_fotografia'] = 0;

            try {
                Sprint84Config::validate($configuracao);
                $this->fail("Era esperada fotografia vazia recusada em {$etapa}.");
            } catch (Sprint84ConfigurationException $erro) {
                $this->assertSame('CONFIG_FOTOGRAFIA_VAZIA', $erro->getErrorCode());
                $this->assertSame(
                    'unidades.0.quantidade_fotografia',
                    $erro->getFieldPath()
                );
            }
        }
    }

    public function testDeveAceitarPrecheckSemEscritasENenhumaAcaoSomenteOracle(): void
    {
        foreach (['PRECHECK', 'NENHUMA_ACAO'] as $etapa) {
            $configuracao = $this->configuracaoValida($etapa);
            $configuracao['allow_oracle_writes'] = $etapa !== 'PRECHECK';
            $configuracao['allow_pncp_writes'] = false;
            $this->assertSame($configuracao, Sprint84Config::validate($configuracao));
        }
    }

    public function testEtapasComEscritaPncpDevemExigirAmbosConsentimentos(): void
    {
        foreach (['PUBLICAR', 'RETIFICAR', 'EXCLUIR_PCA'] as $etapa) {
            foreach (['allow_oracle_writes', 'allow_pncp_writes'] as $campo) {
                $configuracao = $this->configuracaoValida($etapa);
                $configuracao[$campo] = false;
                try {
                    Sprint84Config::validate($configuracao);
                    $this->fail('Era esperado consentimento recusado.');
                } catch (Sprint84ConfigurationException $erro) {
                    $this->assertSame('CONFIG_CONSENTIMENTO_AUSENTE', $erro->getErrorCode());
                }
            }
        }
    }

    public function testEtapasReaisDevemManterEstadoPncpObrigatorio(): void
    {
        foreach (['PUBLICAR', 'NENHUMA_ACAO', 'RETIFICAR', 'EXCLUIR_PCA'] as $etapa) {
            $configuracao = $this->configuracaoValida($etapa);
            $this->assertSame($configuracao, Sprint84Config::validate($configuracao));
            $configuracao['estado_pncp_esperado'] = $etapa === 'PUBLICAR'
                ? 'ENCONTRADO'
                : 'AUSENTE';

            try {
                Sprint84Config::validate($configuracao);
                $this->fail("Era esperado estado PNCP incompatível na etapa {$etapa}.");
            } catch (Sprint84ConfigurationException $erro) {
                $this->assertSame('CONFIG_ESTADO_PNCP_INCOMPATIVEL', $erro->getErrorCode());
                $this->assertSame('estado_pncp_esperado', $erro->getFieldPath());
            }
        }
    }

    public function testPrecheckDeveRecusarEstadoPncpDesconhecido(): void
    {
        $configuracao = $this->configuracaoValida('PRECHECK');
        $configuracao['estado_pncp_esperado'] = 'DESCONHECIDO';

        try {
            Sprint84Config::validate($configuracao);
            $this->fail('Era esperado estado PNCP desconhecido recusado.');
        } catch (Sprint84ConfigurationException $erro) {
            $this->assertSame('CONFIG_VALOR_INVALIDO', $erro->getErrorCode());
            $this->assertSame('estado_pncp_esperado', $erro->getFieldPath());
        }
    }

    public function testErroDeveInformarCodigoCampoEsperadoTipoEValorRecebido(): void
    {
        $configuracao = $this->configuracaoValida();
        $configuracao['ano'] = '2027';

        try {
            Sprint84Config::validate($configuracao);
            $this->fail('Era esperado ano inválido.');
        } catch (Sprint84ConfigurationException $erro) {
            $this->assertSame('CONFIG_TIPO_INVALIDO', $erro->getErrorCode());
            $this->assertSame('ano', $erro->getFieldPath());
            $this->assertSame('inteiro positivo', $erro->getExpected());
            $this->assertSame('string', $erro->getActualType());
            $this->assertSame('2027', $erro->getActualValue());
            $this->assertStringContainsString('ano', $erro->getMessage());
        }
    }

    public function testErroDeCampoSensivelNuncaDeveConterSeuValor(): void
    {
        $erro = new Sprint84ConfigurationException(
            'CONFIG_SEGREDO_INVALIDO',
            'perfil.pncp.senha',
            'texto não vazio',
            'senha-super-secreta'
        );

        $this->assertSame('[PROTEGIDO]', $erro->getActualValue());
        $this->assertStringNotContainsString('senha-super-secreta', $erro->getMessage());
    }

    public function testLoginNaoDeveSerMascaradoNoDiagnostico(): void
    {
        $erro = new Sprint84ConfigurationException(
            'CONFIG_LOGIN_INVALIDO',
            'perfil.pncp.login',
            'texto não vazio',
            'login-de-diagnostico'
        );

        $this->assertSame('login-de-diagnostico', $erro->getActualValue());
        $this->assertStringContainsString('login-de-diagnostico', $erro->getMessage());
    }

    public function testDeveRecusarCaminhoAusenteRelativoInternoEConsentimentosAusentes(): void
    {
        foreach ([false, '', 'config.php', __FILE__] as $indice => $caminho) {
            $caminho === false
                ? putenv(self::VARIAVEL_CONFIGURACAO)
                : putenv(self::VARIAVEL_CONFIGURACAO . '=' . $caminho);

            try {
                Sprint84Config::loadFromEnvironment();
                $this->fail("Era esperado localizador inválido no caso {$indice}.");
            } catch (Sprint84ConfigurationException $erro) {
                $this->assertSame('PCA_SPRINT84_CONFIG_FILE', $erro->getFieldPath());
            }
        }

        foreach (['allow_oracle_writes', 'allow_pncp_writes'] as $campo) {
            $configuracao = $this->configuracaoValida('PUBLICAR');
            $configuracao[$campo] = false;

            try {
                Sprint84Config::validate($configuracao);
                $this->fail("Era esperado consentimento em {$campo}.");
            } catch (Sprint84ConfigurationException $erro) {
                $this->assertSame('CONFIG_CONSENTIMENTO_AUSENTE', $erro->getErrorCode());
                $this->assertSame($campo, $erro->getFieldPath());
            }
        }
    }

    public function testDeveRecusarEstruturaEtapaHashEstadoPncpEUnidadesInvalidos(): void
    {
        $casos = [];

        $extra = $this->configuracaoValida();
        $extra['url'] = 'https://example.test';
        $casos[] = [$extra, 'CONFIG_ESTRUTURA_INVALIDA', '$'];

        $etapa = $this->configuracaoValida();
        $etapa['etapa_autorizada'] = 'DESCONHECIDA';
        $casos[] = [$etapa, 'CONFIG_VALOR_INVALIDO', 'etapa_autorizada'];

        $hash = $this->configuracaoValida('PUBLICAR');
        $hash['oracle_checkpoint_sha256'] = null;
        $casos[] = [$hash, 'CONFIG_HASH_OBRIGATORIO', 'oracle_checkpoint_sha256'];

        $estadoPncp = $this->configuracaoValida('PUBLICAR');
        $estadoPncp['estado_pncp_esperado'] = 'ENCONTRADO';
        $casos[] = [$estadoPncp, 'CONFIG_ESTADO_PNCP_INCOMPATIVEL', 'estado_pncp_esperado'];

        $duplicada = $this->configuracaoValida();
        $duplicada['unidades'][1]['id_unidade_orcamentaria'] = 101;
        $casos[] = [$duplicada, 'CONFIG_UNIDADES_DUPLICADAS', 'unidades'];

        $quantidade = $this->configuracaoValida();
        $quantidade['unidades'][0]['quantidade_demandas'] = 0;
        $casos[] = [$quantidade, 'CONFIG_CARDINALIDADE_INVALIDA', 'unidades.0.quantidade_demandas'];

        foreach ($casos as [$configuracao, $codigo, $campo]) {
            try {
                Sprint84Config::validate($configuracao);
                $this->fail("Era esperada configuração inválida em {$campo}.");
            } catch (Sprint84ConfigurationException $erro) {
                $this->assertSame($codigo, $erro->getErrorCode());
                $this->assertSame($campo, $erro->getFieldPath());
            }
        }
    }

    public function testRetificacaoDeveExigirDiferencasEExclusaoDeveExigirFotografiaVazia(): void
    {
        $retificacao = $this->configuracaoValida('RETIFICAR');
        $retificacao['unidades'][0]['inclusoes_retificacao'] = 0;

        try {
            Sprint84Config::validate($retificacao);
            $this->fail('Era esperada retificação sem inclusão inválida.');
        } catch (Sprint84ConfigurationException $erro) {
            $this->assertSame('CONFIG_RETIFICACAO_SEM_DIFERENCAS', $erro->getErrorCode());
        }

        $exclusao = $this->configuracaoValida('EXCLUIR_PCA');
        $exclusao['unidades'][0]['quantidade_fotografia'] = 1;

        try {
            Sprint84Config::validate($exclusao);
            $this->fail('Era esperada exclusão com fotografia não vazia inválida.');
        } catch (Sprint84ConfigurationException $erro) {
            $this->assertSame('CONFIG_FOTOGRAFIA_NAO_VAZIA', $erro->getErrorCode());
        }
    }

    public function testDeveRecusarServidorDivergenteEPerfilDeProducaoComDetalhes(): void
    {
        $configuracao = $this->configuracaoValida();
        $this->configurarPerfil(PerfilPublicacaoPcaFixture::valido());

        try {
            Sprint84Config::loadHomologationProfile($configuracao, 'outro.test');
            $this->fail('Era esperado servidor divergente.');
        } catch (Sprint84ConfigurationException $erro) {
            $this->assertSame('SERVER_NAME_DIVERGENTE', $erro->getErrorCode());
            $this->assertSame('server_name', $erro->getFieldPath());
            $this->assertSame('outro.test', $erro->getActualValue());
        }

        $producao = PerfilPublicacaoPcaFixture::valido();
        $producao['ambiente'] = 'PRODUCAO';
        $this->configurarPerfil($producao, 'producao');

        try {
            Sprint84Config::loadHomologationProfile(
                $configuracao,
                $configuracao['server_name']
            );
            $this->fail('Era esperado perfil de produção recusado.');
        } catch (Sprint84ConfigurationException $erro) {
            $this->assertSame('PERFIL_PRODUCAO_PROIBIDO', $erro->getErrorCode());
            $this->assertSame('perfil.ambiente', $erro->getFieldPath());
            $this->assertSame('PRODUCAO', $erro->getActualValue());
        }
    }

    /** @return array<string, mixed> */
    private function configuracaoValida(string $etapa = 'PRECHECK'): array
    {
        $estadoPncp = $etapa === 'PUBLICAR' || $etapa === 'PRECHECK'
            ? 'AUSENTE'
            : 'ENCONTRADO';
        $hash = $etapa === 'PRECHECK' ? null : str_repeat('a', 64);
        $fotografia = $etapa === 'EXCLUIR_PCA' ? 0 : 2;
        $inclusoes = $etapa === 'RETIFICAR' ? 1 : 0;
        $exclusoes = $etapa === 'RETIFICAR' ? 1 : 0;

        return [
            'allow_oracle_writes' => true,
            'allow_pncp_writes' => true,
            'server_name' => 'pca.homologacao.test',
            'modo' => 'INDIVIDUAL',
            'ano' => 2027,
            'etapa_autorizada' => $etapa,
            'unidades' => [
                [
                    'id_unidade_orcamentaria' => 101,
                    'quantidade_versoes' => 1,
                    'quantidade_fotografia' => $fotografia,
                    'quantidade_demandas' => 2,
                    'inclusoes_retificacao' => $inclusoes,
                    'exclusoes_retificacao' => $exclusoes,
                ],
                [
                    'id_unidade_orcamentaria' => 202,
                    'quantidade_versoes' => 1,
                    'quantidade_fotografia' => $fotografia,
                    'quantidade_demandas' => 2,
                    'inclusoes_retificacao' => $inclusoes,
                    'exclusoes_retificacao' => $exclusoes,
                ],
            ],
            'oracle_checkpoint_sha256' => $hash,
            'estado_pncp_esperado' => $estadoPncp,
            'sequenciais_limpeza_autorizados' => [],
            'baseline_auditoria' => 0,
        ];
    }

    /** @param array<string, mixed> $perfil */
    private function configurarPerfil(array $perfil, string $sufixo = ''): void
    {
        $caminhoPerfil = $this->escrever("perfil{$sufixo}.php", $perfil);
        $caminhoRegistro = $this->escrever("registro{$sufixo}.php", [
            'pca.homologacao.test' => $caminhoPerfil,
        ]);
        putenv(self::VARIAVEL_REGISTRO . '=' . $caminhoRegistro);
    }

    /** @param mixed $retorno */
    private function escrever(string $nome, mixed $retorno): string
    {
        $caminho = $this->diretorioTemporario . DIRECTORY_SEPARATOR . $nome;
        $conteudo = "<?php\n\nreturn " . var_export($retorno, true) . ";\n";

        if (file_put_contents($caminho, $conteudo) === false) {
            throw new RuntimeException('Não foi possível escrever arquivo temporário.');
        }

        return $caminho;
    }

    private function restaurarVariavel(string $nome, string|false $valor): void
    {
        $valor === false ? putenv($nome) : putenv($nome . '=' . $valor);
    }
}
