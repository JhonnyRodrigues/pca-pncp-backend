<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint83;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Tests\Contract\Sprint83\Support\Sprint83Config;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class Sprint83ConfigTest extends TestCase
{
    private const VARIAVEL_CONFIGURACAO = 'PCA_SPRINT83_CONFIG_FILE';
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';
    private string|false $configuracaoAnterior;
    private string|false $registroAnterior;
    private string $diretorioTemporario;

    protected function setUp(): void
    {
        $this->configuracaoAnterior = getenv(self::VARIAVEL_CONFIGURACAO);
        $this->registroAnterior = getenv(self::VARIAVEL_REGISTRO);
        $base = tempnam(sys_get_temp_dir(), 'pca-s83-config-');

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

        $carregada = Sprint83Config::loadFromEnvironment();
        $perfil = Sprint83Config::loadHomologationProfile(
            $carregada,
            $carregada['server_name']
        );

        $this->assertSame($configuracao, $carregada);
        $this->assertSame(AmbientePncp::HOMOLOGACAO, $perfil->getAmbiente());
    }

    public function testDeveRecusarConfiguracaoAusenteRelativaInternaOuSemConsentimento(): void
    {
        foreach ([false, '', 'config.php', __FILE__] as $caminho) {
            $caminho === false
                ? putenv(self::VARIAVEL_CONFIGURACAO)
                : putenv(self::VARIAVEL_CONFIGURACAO . '=' . $caminho);

            try {
                Sprint83Config::loadFromEnvironment();
                $this->fail('Era esperada configuração externa inválida.');
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }

        $configuracao = $this->configuracaoValida();
        $configuracao['allow_writes'] = false;
        putenv(
            self::VARIAVEL_CONFIGURACAO . '='
            . $this->escrever('sem-consentimento.php', $configuracao)
        );

        $this->expectException(InvalidArgumentException::class);
        Sprint83Config::loadFromEnvironment();
    }

    public function testDeveRecusarEstruturaAdicionalAusenteOuTiposInvalidos(): void
    {
        $casos = [];
        $casos[] = array_replace($this->configuracaoValida(), ['extra' => true]);
        $casos[] = array_replace($this->configuracaoValida(), ['server_name' => 123]);
        $incompleta = $this->configuracaoValida();
        unset($incompleta['massa']['id_demanda_inclusao']);
        $casos[] = $incompleta;
        $tipoInvalido = $this->configuracaoValida();
        $tipoInvalido['massa']['ano'] = '2027';
        $casos[] = $tipoInvalido;
        $itemAdicional = $this->configuracaoValida();
        $itemAdicional['massa']['item']['url'] = 'https://example.test';
        $casos[] = $itemAdicional;

        foreach ($casos as $indice => $caso) {
            putenv(
                self::VARIAVEL_CONFIGURACAO . '='
                . $this->escrever("invalida-{$indice}.php", $caso)
            );

            try {
                Sprint83Config::loadFromEnvironment();
                $this->fail('Era esperada estrutura inválida.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testDeveRecusarDataForaDoAnoIdsNaoLacunadosEResiduoInvalido(): void
    {
        $casos = [];
        $data = $this->configuracaoValida();
        $data['massa']['data_desejada'] = '2028-01-01';
        $casos[] = $data;
        $ids = $this->configuracaoValida();
        $ids['massa']['id_demanda_publicacao_2'] = 1002;
        $casos[] = $ids;
        $residuo = $this->configuracaoValida();
        $residuo['massa']['residuo_sequencial_autorizado'] = 0;
        $casos[] = $residuo;

        foreach ($casos as $indice => $caso) {
            putenv(
                self::VARIAVEL_CONFIGURACAO . '='
                . $this->escrever("massa-invalida-{$indice}.php", $caso)
            );

            try {
                Sprint83Config::loadFromEnvironment();
                $this->fail('Era esperada massa inválida.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testDeveRecusarServidorDivergentePerfilDeProducaoECnpjInvalido(): void
    {
        $configuracao = $this->configuracaoValida();
        $casos = [];
        $casos[] = ['outro.test', PerfilPublicacaoPcaFixture::valido()];
        $producao = PerfilPublicacaoPcaFixture::valido();
        $producao['ambiente'] = 'PRODUCAO';
        $casos[] = [$configuracao['server_name'], $producao];
        $cnpjInvalido = PerfilPublicacaoPcaFixture::valido();
        $cnpjInvalido['cnpj'] = 'invalido';
        $casos[] = [$configuracao['server_name'], $cnpjInvalido];

        foreach ($casos as $indice => [$serverName, $perfil]) {
            $this->configurarPerfil($perfil, "{$indice}");

            try {
                Sprint83Config::loadHomologationProfile($configuracao, $serverName);
                $this->fail('Era esperado perfil ou servidor inválido.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /** @return array<string, mixed> */
    private function configuracaoValida(): array
    {
        return [
            'allow_writes' => true,
            'server_name' => 'pca.homologacao.test',
            'massa' => [
                'ano' => 2027,
                'data_desejada' => '2027-06-30',
                'codigo_uasg' => '123456',
                'descricao_uasg' => 'Massa dedicada Sprint 8.3',
                'id_demanda_publicacao_1' => 1001,
                'id_demanda_publicacao_2' => 1003,
                'id_demanda_inclusao' => 1005,
                'residuo_sequencial_autorizado' => null,
                'item' => [
                    'categoria' => 1,
                    'catalogo' => 1,
                    'unidade_medida' => 'UNIDADE',
                    'codigo_item' => '123',
                    'grupo' => '45',
                    'descricao_grupo' => 'Grupo da massa dedicada',
                    'origem' => 'N',
                ],
            ],
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
