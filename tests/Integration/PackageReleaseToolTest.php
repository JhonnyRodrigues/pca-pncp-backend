<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PackageReleaseToolTest extends TestCase
{
    /** @var string[] */
    private array $temporarios = [];

    protected function tearDown(): void
    {
        foreach ($this->temporarios as $temporario) {
            $this->removerArvore($temporario);
        }
    }

    public function testComposerDeveExporArquivoEReleaseCompleta(): void
    {
        $composer = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            32,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            'php tools/package/archive.php',
            $composer['scripts']['package:archive'] ?? null
        );
        $this->assertSame(
            ['@package:build', '@package:verify', '@package:archive'],
            $composer['scripts']['package:release'] ?? null
        );
    }

    public function testDeveCriarTarGzSemSobrescreverArquivoExistente(): void
    {
        $tool = dirname(__DIR__, 2) . '/tools/package/archive.php';
        $this->assertFileExists($tool);
        require_once $tool;

        $base = $this->novoDiretorioTemporario();
        $pacote = $base . '/pca-pncp-backend';
        mkdir($pacote . '/src', 0775, true);
        mkdir($pacote . '/vendor', 0775, true);
        file_put_contents($pacote . '/src/Exemplo.php', '<?php');
        file_put_contents($pacote . '/vendor/autoload.php', '<?php');
        file_put_contents($pacote . '/PACKAGE-MANIFEST.json', '{}');

        $data = packageArchiveDate(new \DateTimeImmutable('2026-09-22T18:30:45-03:00'));
        $this->assertSame('20260922', $data);
        $nome = packageArchiveFileName($data, 'abc1234');
        $this->assertSame('pca-pncp-backend_20260922_abc1234.tar.gz', $nome);

        $arquivo = $base . '/' . $nome;
        $resultado = packageArchiveCreate($pacote, $arquivo);

        $this->assertFileExists($arquivo);
        $this->assertSame(hash_file('sha256', $arquivo), $resultado['sha256']);
        $this->assertGreaterThan(0, $resultado['tamanho_bytes']);

        $entradas = packageArchiveListEntries($arquivo);
        $this->assertNotEmpty($entradas);
        foreach ($entradas as $entrada) {
            $this->assertMatchesRegularExpression(
                '#^pca-pncp-backend(?:/|$)#',
                $entrada
            );
            $this->assertStringNotContainsString('../', $entrada);
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('já existe');
        packageArchiveCreate($pacote, $arquivo);
    }

    public function testDeveRecusarWorktreeComAlteracoesNaoCommitadas(): void
    {
        $tool = dirname(__DIR__, 2) . '/tools/package/archive.php';
        $this->assertFileExists($tool);
        require_once $tool;

        $repositorio = $this->novoDiretorioTemporario();
        $this->executar(['git', '-C', $repositorio, 'init', '--quiet']);
        file_put_contents($repositorio . '/versionado.txt', 'versão inicial');
        $this->executar(['git', '-C', $repositorio, 'add', 'versionado.txt']);
        $this->executar([
            'git',
            '-C',
            $repositorio,
            '-c',
            'user.name=Teste',
            '-c',
            'user.email=teste@example.invalid',
            'commit',
            '--quiet',
            '-m',
            'commit inicial',
        ]);

        $this->assertSame(7, strlen(packageArchiveCommitId($repositorio)));
        packageArchiveAssertCleanWorktree($repositorio);

        file_put_contents($repositorio . '/nao-versionado.txt', 'alteração local');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('alterações não commitadas');
        packageArchiveAssertCleanWorktree($repositorio);
    }

    private function novoDiretorioTemporario(): string
    {
        $temporario = tempnam(sys_get_temp_dir(), 'pca-release-');
        $this->assertIsString($temporario);
        unlink($temporario);
        mkdir($temporario, 0775, true);
        $this->temporarios[] = $temporario;

        return $temporario;
    }

    /** @param string[] $comando */
    private function executar(array $comando): void
    {
        $processo = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($processo);

        $saida = stream_get_contents($pipes[1]);
        $erro = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $this->assertSame(0, proc_close($processo), $saida . $erro);
    }

    private function removerArvore(string $diretorio): void
    {
        if (!is_dir($diretorio)) {
            return;
        }

        $itens = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($diretorio, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($itens as $item) {
            chmod($item->getPathname(), 0777);
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        chmod($diretorio, 0777);
        rmdir($diretorio);
    }
}
