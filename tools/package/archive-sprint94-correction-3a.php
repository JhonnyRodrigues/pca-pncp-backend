<?php

/** Compacta o artefato corretivo verificado sem identificá-lo como release oficial. */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$buildRoot = $projectRoot . DIRECTORY_SEPARATOR . 'build';
$artifact = $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-sprint94-correcao-3a';
$archive = $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-sprint94-correcao-3a.tar.gz';

sprint94CorrectionArchiveRun([
    PHP_BINARY,
    $projectRoot . '/tools/package/verify-sprint94-correction-3a.php',
    $artifact,
]);

if (is_file($archive)) {
    sprint94CorrectionArchiveFail('O arquivo corretivo ja existe; remova-o conscientemente antes de recriar.');
}

try {
    sprint94CorrectionArchiveRun([
        PHP_OS_FAMILY === 'Windows' ? 'tar.exe' : 'tar',
        '-czf',
        $archive,
        '-C',
        dirname($artifact),
        basename($artifact),
    ]);

    $listing = sprint94CorrectionArchiveRun([
        PHP_OS_FAMILY === 'Windows' ? 'tar.exe' : 'tar',
        '-tzf',
        $archive,
    ]);
    $entries = array_values(array_filter(
        preg_split('/\R/', trim($listing)) ?: [],
        static fn (string $entry): bool => $entry !== ''
    ));
    $root = preg_quote(basename($artifact), '#');
    if ($entries === []) {
        throw new RuntimeException('O arquivo corretivo esta vazio.');
    }
    foreach ($entries as $entry) {
        if (
            preg_match("#^{$root}(?:/|$)#D", $entry) !== 1
            || preg_match('#(^|/)\.\.(?:/|$)#D', $entry) === 1
        ) {
            throw new RuntimeException('O arquivo corretivo possui topologia insegura.');
        }
    }

    $size = filesize($archive);
    $sha256 = hash_file('sha256', $archive);
    if (!is_int($size) || $size <= 0 || !is_string($sha256)) {
        throw new RuntimeException('Nao foi possivel medir o arquivo corretivo.');
    }

    echo json_encode(
        [
            'resultado' => 'CORRECAO_SPRINT94_3A_ARQUIVADA',
            'arquivo' => $archive,
            'release_oficial' => false,
            'tamanho_bytes' => $size,
            'sha256' => $sha256,
            'entradas' => count($entries),
            'topologia_segura' => true,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ), PHP_EOL;
} catch (Throwable $exception) {
    if (is_file($archive)) {
        unlink($archive);
    }
    sprint94CorrectionArchiveFail($exception->getMessage());
}

/** @param string[] $command */
function sprint94CorrectionArchiveRun(array $command): string
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        sprint94CorrectionArchiveFail('Nao foi possivel executar uma etapa do arquivamento.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException(trim($stdout . $stderr) ?: 'Uma etapa do arquivamento falhou.');
    }

    return $stdout;
}

function sprint94CorrectionArchiveFail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
