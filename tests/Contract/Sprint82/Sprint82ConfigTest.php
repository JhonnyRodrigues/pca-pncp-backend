<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint82;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Infrastructure\Pncp\Http\AmbientePncp;
use Tests\Contract\Sprint82\Support\Sprint82Config;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class Sprint82ConfigTest extends TestCase
{
    private const VARIAVEL_CONFIGURACAO = 'PCA_SPRINT82_CONFIG_FILE';
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';
    private string|false $configuracaoAnterior;
    private string|false $registroAnterior;
    private string $diretorioTemporario;

    protected function setUp(): void
    {
        $this->configuracaoAnterior = getenv(self::VARIAVEL_CONFIGURACAO);
        $this->registroAnterior = getenv(self::VARIAVEL_REGISTRO);
        $base = tempnam(sys_get_temp_dir(), 'pca-s82-config-');

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

        $carregada = Sprint82Config::loadFromEnvironment();
        $perfil = Sprint82Config::loadHomologationProfile(
            $carregada,
            $carregada['server_name']
        );

        $this->assertSame($configuracao, $carregada);
        $this->assertSame(AmbientePncp::HOMOLOGACAO, $perfil->getAmbiente());
    }

    public function testDeveRecusarConfiguracaoAusenteRelativaInternaOuSemConsentimento(): void
    {
        $casos = [false, '', 'config.php', __FILE__];

        foreach ($casos as $caminho) {
            $caminho === false
                ? putenv(self::VARIAVEL_CONFIGURACAO)
                : putenv(self::VARIAVEL_CONFIGURACAO . '=' . $caminho);

            try {
                Sprint82Config::loadFromEnvironment();
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
        Sprint82Config::loadFromEnvironment();
    }

    public function testDeveRecusarEstruturaAdicionalETiposInvalidos(): void
    {
        $casos = [];
        $casos[] = array_replace($this->configuracaoValida(), ['extra' => true]);
        $casos[] = array_replace($this->configuracaoValida(), ['server_name' => 123]);
        $incompleta = $this->configuracaoValida();
        unset($incompleta['massa']['sequencial_teste']);
        $casos[] = $incompleta;
        $tipoInvalido = $this->configuracaoValida();
        $tipoInvalido['massa']['ano'] = '2027';
        $casos[] = $tipoInvalido;
        $lockExcessivo = $this->configuracaoValida();
        $lockExcessivo['massa']['lock_hold_seconds'] = 61;
        $casos[] = $lockExcessivo;

        foreach ($casos as $indice => $caso) {
            putenv(
                self::VARIAVEL_CONFIGURACAO . '='
                . $this->escrever("invalida-{$indice}.php", $caso)
            );

            try {
                Sprint82Config::loadFromEnvironment();
                $this->fail('Era esperada estrutura inválida.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testDeveRecusarServidorDivergente(): void
    {
        $configuracao = $this->configuracaoValida();
        $this->configurarPerfil(PerfilPublicacaoPcaFixture::valido());

        $this->expectException(InvalidArgumentException::class);

        Sprint82Config::loadHomologationProfile($configuracao, 'outro.test');
    }

    public function testDeveRecusarPerfilDeProducao(): void
    {
        $configuracao = $this->configuracaoValida();
        $perfilProducao = PerfilPublicacaoPcaFixture::valido();
        $perfilProducao['ambiente'] = 'PRODUCAO';
        $this->configurarPerfil($perfilProducao);

        $this->expectException(InvalidArgumentException::class);

        Sprint82Config::loadHomologationProfile(
            $configuracao,
            $configuracao['server_name']
        );
    }

    /** @return array<string, mixed> */
    private function configuracaoValida(): array
    {
        return [
            'allow_writes' => true,
            'server_name' => 'pca.homologacao.test',
            'massa' => [
                'ano' => 2027,
                'id_unidade_orcamentaria' => 10,
                'sequencial_teste' => 82,
                'mensagem_auditoria' => 'PCA-S82-INTEGRACAO-ORACLE',
                'lock_hold_seconds' => 30,
            ],
        ];
    }

    /** @param array<string, mixed> $perfil */
    private function configurarPerfil(array $perfil): void
    {
        $caminhoPerfil = $this->escrever('perfil.php', $perfil);
        $caminhoRegistro = $this->escrever('registro.php', [
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
