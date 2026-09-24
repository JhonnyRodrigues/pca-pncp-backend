<?php

/** Monta o artefato corretivo nominal da Sprint 9.4, separado da release oficial. */

declare(strict_types=1);

const SPRINT94_CORRECTION_3A_FORMAT = 'PCA_PNCP_SPRINT94_CORRECAO_3A_V1';

$projectRoot = dirname(__DIR__, 2);
$buildRoot = $projectRoot . DIRECTORY_SEPARATOR . 'build';
$backendPackage = $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-backend';
$output = sprint94CorrectionNormalizePath(
    $argv[1] ?? $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-sprint94-correcao-3a'
);
$allowedRoot = sprint94CorrectionNormalizePath($buildRoot) . DIRECTORY_SEPARATOR;

if (!str_starts_with($output . DIRECTORY_SEPARATOR, $allowedRoot)) {
    sprint94CorrectionFail('O artefato deve ser gerado dentro do diretorio build do projeto.');
}

sprint94CorrectionRun([PHP_BINARY, $projectRoot . '/tools/package/build.php']);
sprint94CorrectionRun([
    PHP_BINARY,
    $projectRoot . '/tools/package/verify.php',
    $backendPackage,
]);

sprint94CorrectionRemoveTree($output);
sprint94CorrectionMakeDirectory($output);
sprint94CorrectionCopyTree(
    $backendPackage,
    $output . DIRECTORY_SEPARATOR . 'pca-pncp-backend'
);

$blankRelative = 'scriptcase/PCA_PUBLICAR_B/onExecute.php';
$blankSource = $projectRoot . DIRECTORY_SEPARATOR
    . 'integracao' . DIRECTORY_SEPARATOR . 'scriptcase' . DIRECTORY_SEPARATOR
    . 'PCA_PUBLICAR_B' . DIRECTORY_SEPARATOR . 'onExecute.php';
$blankDestination = $output . DIRECTORY_SEPARATOR
    . str_replace('/', DIRECTORY_SEPARATOR, $blankRelative);
sprint94CorrectionCopyFile($blankSource, $blankDestination);

$manifest = [
    'formato' => SPRINT94_CORRECTION_3A_FORMAT,
    'finalidade' => 'Implantacao nominal da correcao 3A da Sprint 9.4',
    'release_oficial' => false,
    'configuracoes_ou_segredos' => false,
    'backend_manifest_sha256' => hash_file(
        'sha256',
        $output . '/pca-pncp-backend/PACKAGE-MANIFEST.json'
    ),
    'blank_sha256' => hash_file('sha256', $blankDestination),
    'arquivos' => sprint94CorrectionFileHashes($output),
];

file_put_contents(
    $output . DIRECTORY_SEPARATOR . 'CORRECAO-MANIFEST.json',
    json_encode(
        $manifest,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ) . PHP_EOL
);

echo json_encode(
    [
        'resultado' => 'CORRECAO_SPRINT94_3A_GERADA',
        'diretorio' => $output,
        'release_oficial' => false,
        'arquivos_verificados' => count($manifest['arquivos']),
        'configuracoes_ou_segredos' => false,
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
), PHP_EOL;

function sprint94CorrectionNormalizePath(string $path): string
{
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    if (preg_match('/^(?:[A-Za-z]:)?' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', $path) !== 1) {
        $path = getcwd() . DIRECTORY_SEPARATOR . $path;
    }

    $parts = [];
    foreach (explode(DIRECTORY_SEPARATOR, $path) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $part;
    }

    $prefix = preg_match('/^[A-Za-z]:$/', $parts[0] ?? '') === 1
        ? array_shift($parts) . DIRECTORY_SEPARATOR
        : DIRECTORY_SEPARATOR;

    return rtrim($prefix . implode(DIRECTORY_SEPARATOR, $parts), DIRECTORY_SEPARATOR);
}

function sprint94CorrectionMakeDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        sprint94CorrectionFail('Nao foi possivel criar diretorio do artefato corretivo.');
    }
}

function sprint94CorrectionRemoveTree(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($path);
}

function sprint94CorrectionCopyTree(string $source, string $destination): void
{
    sprint94CorrectionMakeDirectory($destination);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($source) + 1);
        $target = $destination . DIRECTORY_SEPARATOR . $relative;
        $item->isDir()
            ? sprint94CorrectionMakeDirectory($target)
            : sprint94CorrectionCopyFile($item->getPathname(), $target);
    }
}

function sprint94CorrectionCopyFile(string $source, string $destination): void
{
    if (!is_file($source)) {
        sprint94CorrectionFail("Arquivo necessario ausente: {$source}");
    }
    sprint94CorrectionMakeDirectory(dirname($destination));
    if (!copy($source, $destination)) {
        sprint94CorrectionFail('Nao foi possivel copiar arquivo para o artefato corretivo.');
    }
}

/** @param string[] $command */
function sprint94CorrectionRun(array $command): void
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        sprint94CorrectionFail('Nao foi possivel executar uma etapa de construcao.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        fwrite(STDERR, $stdout . $stderr);
        sprint94CorrectionFail('Uma etapa de construcao do artefato falhou.');
    }
}

/** @return array<string, string> */
function sprint94CorrectionFileHashes(string $root): array
{
    $hashes = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $item) {
        if (!$item->isFile() || $item->getFilename() === 'CORRECAO-MANIFEST.json') {
            continue;
        }
        $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($root) + 1));
        $hashes[$relative] = hash_file('sha256', $item->getPathname());
    }
    ksort($hashes, SORT_STRING);

    return $hashes;
}

function sprint94CorrectionFail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
