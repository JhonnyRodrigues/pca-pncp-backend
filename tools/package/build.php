<?php

/**
 * Apaga o pacote candidato anterior, copia o código, executa Composer, gera o autoload e cria o manifesto.
 */

declare(strict_types=1);

const PACKAGE_FORMAT = 'PCA_PNCP_BACKEND_V1';

$projectRoot = dirname(__DIR__, 2);
$buildRoot = $projectRoot . DIRECTORY_SEPARATOR . 'build';
$output = $argv[1] ?? $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-backend';
$output = normalizePath($output);
$allowedRoot = normalizePath($buildRoot) . DIRECTORY_SEPARATOR;

if (!str_starts_with($output . DIRECTORY_SEPARATOR, $allowedRoot)) {
    fail('O pacote deve ser gerado dentro do diretório build do projeto.');
}

removeTree($output);
makeDirectory($output);

copyTree($projectRoot . DIRECTORY_SEPARATOR . 'src', $output . DIRECTORY_SEPARATOR . 'src');
copyFile($projectRoot . DIRECTORY_SEPARATOR . 'composer.json', $output . DIRECTORY_SEPARATOR . 'composer.json');
copyFile($projectRoot . DIRECTORY_SEPARATOR . 'composer.lock', $output . DIRECTORY_SEPARATOR . 'composer.lock');
configurePhp81Platform($output . DIRECTORY_SEPARATOR . 'composer.json');

$composerCommand = composerCommand();
$composerCache = $buildRoot . DIRECTORY_SEPARATOR . 'composer-cache';
makeDirectory($composerCache);
putenv('COMPOSER_CACHE_DIR=' . $composerCache);

run(array_merge($composerCommand, [
    'install',
    '--working-dir=' . $output,
    '--no-dev',
    '--prefer-dist',
    '--optimize-autoloader',
    '--classmap-authoritative',
    '--no-interaction',
    '--no-progress',
]));

run([
    PHP_BINARY,
    '-r',
    'require $argv[1]; exit(class_exists($argv[2]) ? 0 : 1);',
    $output . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
    'Semae\\Pca\\PublicadorPcaScriptcase',
]);

run([
    PHP_BINARY,
    '-r',
    'require $argv[1]; exit(class_exists($argv[2]) ? 0 : 1);',
    $output . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
    'Semae\\Pca\\RegistradorEventosTecnicosScriptcase',
]);

$composerLockHash = hash_file('sha256', $output . DIRECTORY_SEPARATOR . 'composer.lock');
unlink($output . DIRECTORY_SEPARATOR . 'composer.json');
unlink($output . DIRECTORY_SEPARATOR . 'composer.lock');

$manifest = [
    'formato' => PACKAGE_FORMAT,
    'php_minimo' => '8.1',
    'entrada_publica' => 'Semae\\Pca\\PublicadorPcaScriptcase',
    'registrador_publico' => 'Semae\\Pca\\RegistradorEventosTecnicosScriptcase',
    'composer_lock_sha256' => $composerLockHash,
    'arquivos' => fileHashes($output),
];

file_put_contents(
    $output . DIRECTORY_SEPARATOR . 'PACKAGE-MANIFEST.json',
    json_encode(
        $manifest,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ) . PHP_EOL
);

echo json_encode(
    [
        'resultado' => 'PACOTE_GERADO',
        'diretorio' => $output,
        'arquivos_verificados' => count($manifest['arquivos']),
        'arquivos_totais' => count($manifest['arquivos']) + 1,
        'composer_lock_sha256' => $manifest['composer_lock_sha256'],
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
), PHP_EOL;

function normalizePath(string $path): string
{
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    if (!preg_match('/^(?:[A-Za-z]:)?' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', $path)) {
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

function makeDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        fail('Não foi possível criar o diretório do pacote.');
    }
}

function removeTree(string $path): void
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

function copyTree(string $source, string $destination): void
{
    makeDirectory($destination);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($source) + 1);
        $target = $destination . DIRECTORY_SEPARATOR . $relative;
        $item->isDir() ? makeDirectory($target) : copyFile($item->getPathname(), $target);
    }
}

function copyFile(string $source, string $destination): void
{
    makeDirectory(dirname($destination));
    if (!copy($source, $destination)) {
        fail('Não foi possível copiar um arquivo para o pacote.');
    }
}

function configurePhp81Platform(string $composerJson): void
{
    $configuration = json_decode(
        (string) file_get_contents($composerJson),
        true,
        32,
        JSON_THROW_ON_ERROR
    );
    if (!is_array($configuration)) {
        fail('O composer.json do pacote é inválido.');
    }

    $configuration['config']['platform']['php'] = '8.1.0';
    file_put_contents(
        $composerJson,
        json_encode(
            $configuration,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ) . PHP_EOL
    );
}

/** @return string[] */
function composerCommand(): array
{
    $configured = getenv('COMPOSER_BINARY');
    if (is_string($configured) && trim($configured) !== '') {
        return str_ends_with(strtolower($configured), '.phar')
            ? [PHP_BINARY, $configured]
            : [$configured];
    }

    if (PHP_OS_FAMILY !== 'Windows') {
        return ['composer'];
    }

    $path = getenv('PATH');
    foreach (explode(PATH_SEPARATOR, is_string($path) ? $path : '') as $directory) {
        $phar = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . 'composer.phar';
        if (is_file($phar)) {
            return [PHP_BINARY, $phar];
        }
    }

    fail('composer.phar não foi encontrado no PATH.');
}

/** @param string[] $command */
function run(array $command): void
{
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        fail('Não foi possível iniciar uma etapa de construção do pacote.');
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        fwrite(STDERR, $stdout . $stderr);
        fail('Uma etapa de construção do pacote falhou.');
    }
}

/** @return array<string, string> */
function fileHashes(string $root): array
{
    $hashes = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $item) {
        if (!$item->isFile() || $item->getFilename() === 'PACKAGE-MANIFEST.json') {
            continue;
        }
        $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($root) + 1));
        $hashes[$relative] = hash_file('sha256', $item->getPathname());
    }

    ksort($hashes, SORT_STRING);

    return $hashes;
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
