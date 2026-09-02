<?php

declare(strict_types=1);

namespace Tests\Composition;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Semae\Pca\Composition\CarregadorPerfilPublicacaoPca;

final class CarregadorPerfilPublicacaoPcaTest extends TestCase
{
    private const VARIAVEL_REGISTRO = 'PCA_PNCP_PROFILE_REGISTRY_FILE';
    private string|false $registroAnterior;
    private string $diretorioTemporario;

    protected function setUp(): void
    {
        $this->registroAnterior = getenv(self::VARIAVEL_REGISTRO);
        $base = tempnam(sys_get_temp_dir(), 'pca-s82-loader-');

        if ($base === false || !unlink($base) || !mkdir($base)) {
            throw new RuntimeException('Não foi possível preparar o diretório temporário.');
        }

        $this->diretorioTemporario = $base;
    }

    protected function tearDown(): void
    {
        if ($this->registroAnterior === false) {
            putenv(self::VARIAVEL_REGISTRO);
        } else {
            putenv(self::VARIAVEL_REGISTRO . '=' . $this->registroAnterior);
        }

        foreach (glob($this->diretorioTemporario . DIRECTORY_SEPARATOR . '*') ?: [] as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }
        rmdir($this->diretorioTemporario);
    }

    public function testDeveCarregarPerfilSelecionadoLiteralmente(): void
    {
        $perfil = $this->escrever('perfil.php', ['marcador' => 'perfil-exato']);
        $registro = $this->escrever('registro.php', ['pca.exato.test' => $perfil]);
        putenv(self::VARIAVEL_REGISTRO . '=' . $registro);

        $carregado = (new CarregadorPerfilPublicacaoPca())->carregar('pca.exato.test');

        $this->assertSame(['marcador' => 'perfil-exato'], $carregado);
    }

    public function testDeveRecusarServidorNormalizadoOuDesconhecido(): void
    {
        $perfil = $this->escrever('perfil.php', ['marcador' => 'perfil-exato']);
        $registro = $this->escrever('registro.php', ['pca.exato.test' => $perfil]);
        putenv(self::VARIAVEL_REGISTRO . '=' . $registro);

        foreach (['PCA.EXATO.TEST', ' pca.exato.test', 'pca.exato.test '] as $servidor) {
            try {
                (new CarregadorPerfilPublicacaoPca())->carregar($servidor);
                $this->fail('Era esperado servidor não autorizado.');
            } catch (\UnexpectedValueException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testDeveValidarTodoORegistroAntesDaSelecao(): void
    {
        $perfil = $this->escrever('perfil.php', ['marcador' => 'perfil-exato']);
        $registro = $this->escrever('registro.php', [
            'pca.exato.test' => $perfil,
            'outro.test' => $this->diretorioTemporario . DIRECTORY_SEPARATOR . 'ausente.php',
        ]);
        putenv(self::VARIAVEL_REGISTRO . '=' . $registro);

        $this->expectException(RuntimeException::class);

        (new CarregadorPerfilPublicacaoPca())->carregar('pca.exato.test');
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
}
