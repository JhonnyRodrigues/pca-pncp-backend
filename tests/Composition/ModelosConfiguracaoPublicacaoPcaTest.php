<?php

declare(strict_types=1);

namespace Tests\Composition;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Composition\CarregadorPerfilPublicacaoPca;
use Semae\Pca\Composition\PerfilPublicacaoPca;
use Semae\Pca\Domain\ValueObjects\Cnpj;
use UnexpectedValueException;

final class ModelosConfiguracaoPublicacaoPcaTest extends TestCase
{
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';
    private string|false $registroAnterior;
    private string $diretorioTemporario;

    protected function setUp(): void
    {
        $this->registroAnterior = getenv(self::VARIAVEL_REGISTRO);
        $base = tempnam(sys_get_temp_dir(), 'pca-modelos-');

        if ($base === false || !unlink($base) || !mkdir($base)) {
            throw new RuntimeException('Não foi possível preparar o diretório temporário.');
        }

        $this->diretorioTemporario = $base;
        // O contrato exige arquivo legível; este conteúdo não é um certificado TLS.
        $this->escrever('ca-bundle.crt', 'Arquivo fictício para validação estrutural.');
    }

    protected function tearDown(): void
    {
        putenv(self::VARIAVEL_REGISTRO . (
            $this->registroAnterior === false ? '' : '=' . $this->registroAnterior
        ));

        foreach (['registro.php', 'perfil-producao.php', 'ca-bundle.crt'] as $nome) {
            $caminho = $this->diretorioTemporario . DIRECTORY_SEPARATOR . $nome;

            if (is_file($caminho)) {
                unlink($caminho);
            }
        }
        rmdir($this->diretorioTemporario);
    }

    /** @return iterable<string, array{string}> */
    public static function ambientes(): iterable
    {
        yield 'homologação' => ['HOMOLOGACAO'];
        yield 'produção' => ['PRODUCAO'];
    }

    #[DataProvider('ambientes')]
    public function testModeloPreenchidoDeveSerAceitoPelosValidadores(string $ambiente): void
    {
        $perfil = $this->preencherPerfil($ambiente);

        $this->assertInstanceOf(PerfilPublicacaoPca::class, new PerfilPublicacaoPca($perfil));
        $this->assertInstanceOf(Cnpj::class, new Cnpj($perfil['cnpj']));
    }

    public function testModeloSemPreenchimentoDeveContinuarIncompleto(): void
    {
        $perfil = require dirname(__DIR__, 2) . '/config/perfil.example.php';

        $this->assertTrue($perfil['cnpj'] === '', 'O modelo deve manter CNPJ vazio.');
        $this->assertTrue($perfil['pncp']['login'] === '', 'O modelo deve manter login vazio.');
        $this->assertTrue($perfil['pncp']['senha'] === '', 'O modelo deve manter senha vazia.');
        $this->expectException(InvalidArgumentException::class);

        new PerfilPublicacaoPca($perfil);
    }

    public function testRegistroCopiadoDeveCarregarPerfilExternoSomentePeloHostnameLiteral(): void
    {
        $modeloRegistro = file_get_contents(dirname(__DIR__, 2) . '/config/registro.example.php');

        if ($modeloRegistro === false) {
            throw new RuntimeException('Não foi possível ler o modelo de registro.');
        }

        $registro = $this->escrever(
            'registro.php',
            str_replace('SERVER_NAME_EXATO_DE_PRODUCAO', 'pca.exato.test', $modeloRegistro)
        );
        $perfil = $this->preencherPerfil('PRODUCAO');
        $this->escrever('perfil-producao.php', "<?php\nreturn " . var_export($perfil, true) . ";\n");
        putenv(self::VARIAVEL_REGISTRO . '=' . $registro);
        $carregador = new CarregadorPerfilPublicacaoPca();

        $carregado = $carregador->carregar('pca.exato.test');

        $this->assertSame($perfil, $carregado);
        $this->assertInstanceOf(PerfilPublicacaoPca::class, new PerfilPublicacaoPca($carregado));
        $this->assertInstanceOf(Cnpj::class, new Cnpj($carregado['cnpj']));
        $this->expectException(UnexpectedValueException::class);

        $carregador->carregar('PCA.EXATO.TEST');
    }

    /** @return array<string, mixed> */
    private function preencherPerfil(string $ambiente): array
    {
        $perfil = require dirname(__DIR__, 2) . '/config/perfil.example.php';
        $perfil['ambiente'] = $ambiente;
        $perfil['cnpj'] = '12345678000195';
        $perfil['pncp']['login'] = 'login-ficticio';
        $perfil['pncp']['senha'] = 'senha-ficticia';
        $perfil['pncp']['ca_bundle'] = $this->diretorioTemporario . DIRECTORY_SEPARATOR . 'ca-bundle.crt';

        return $perfil;
    }

    private function escrever(string $nome, string $conteudo): string
    {
        $caminho = $this->diretorioTemporario . DIRECTORY_SEPARATOR . $nome;

        if (file_put_contents($caminho, $conteudo) === false) {
            throw new RuntimeException('Não foi possível escrever arquivo temporário.');
        }

        return $caminho;
    }
}
