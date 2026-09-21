<?php

/**
 * Monta o artefato temporário usado pelo gate de contenção da Sprint 8.5.
 */

declare(strict_types=1);

const CONTAINMENT_GATE_FORMAT = 'PCA_PNCP_CONTAINMENT_GATE_V1';

$projectRoot = dirname(__DIR__, 2);
$buildRoot = $projectRoot . DIRECTORY_SEPARATOR . 'build';
$backendPackage = $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-backend';
$output = normalizePath(
    $argv[1] ?? $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-containment-gate'
);
$allowedRoot = normalizePath($buildRoot) . DIRECTORY_SEPARATOR;

if (!str_starts_with($output . DIRECTORY_SEPARATOR, $allowedRoot)) {
    fail('O artefato do gate deve ser gerado dentro do diretório build do projeto.');
}

run([
    PHP_BINARY,
    $projectRoot . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'package'
        . DIRECTORY_SEPARATOR . 'verify.php',
    $backendPackage,
]);

removeTree($output);
makeDirectory($output);
copyTree($backendPackage, $output . DIRECTORY_SEPARATOR . 'backend');

$runtimeFiles = [
    'tests/Contract/Sprint84/Support/OracleCheckpointExecutor.php',
    'tests/Contract/Sprint84/Support/OracleCheckpointQueries.php',
    'tests/Contract/Sprint84/Support/OracleCheckpointSnapshot.php',
    'tests/Contract/Sprint84/Support/Sprint84AdodbGuard.php',
    'tests/Contract/Sprint84/Support/Sprint84ConfigurationException.php',
    'tests/Contract/Sprint84/Support/Sprint84GateException.php',
    'tests/Contract/Sprint84/Support/Sprint84PncpTransportGuard.php',
    'tests/Contract/Sprint85/Support/Sprint85Config.php',
    'tests/Contract/Sprint85/Support/Sprint85ContainmentGate.php',
    'tests/Contract/Sprint85/Support/Sprint85OracleLockProbe.php',
    'tests/Contract/Sprint85/Support/Sprint85OracleQueries.php',
    'tests/Contract/Sprint85/Support/Sprint85Precheck.php',
    'tests/Contract/Sprint85/scriptcase_containment_probe.php',
];

foreach ($runtimeFiles as $relative) {
    copyFile(
        $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative),
        $output . DIRECTORY_SEPARATOR . 'gate' . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relative)
    );
}

$bootstrap = <<<'PHP'
<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Tests\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = __DIR__ . '/tests/' . $relative . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/tests/Contract/Sprint85/scriptcase_containment_probe.php';
PHP;

file_put_contents(
    $output . DIRECTORY_SEPARATOR . 'gate' . DIRECTORY_SEPARATOR . 'bootstrap.php',
    $bootstrap . PHP_EOL
);

$manifest = [
    'formato' => CONTAINMENT_GATE_FORMAT,
    'php_minimo' => '8.1',
    'finalidade' => 'Gate temporário de contenção da Sprint 8.5 no validaSGI',
    'backend_manifest_sha256' => hash_file(
        'sha256',
        $output . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'PACKAGE-MANIFEST.json'
    ),
    'arquivos' => fileHashes($output),
];

file_put_contents(
    $output . DIRECTORY_SEPARATOR . 'GATE-MANIFEST.json',
    json_encode(
        $manifest,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ) . PHP_EOL
);

echo json_encode(
    [
        'resultado' => 'ARTEFATO_CONTENCAO_GERADO',
        'diretorio' => $output,
        'arquivos_verificados' => count($manifest['arquivos']),
        'arquivos_totais' => count($manifest['arquivos']) + 1,
        'configuracoes_ou_segredos' => false,
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
        fail('Não foi possível criar um diretório do artefato.');
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
    if (!is_file($source)) {
        fail("Arquivo necessário ausente: {$source}");
    }
    makeDirectory(dirname($destination));
    if (!copy($source, $destination)) {
        fail('Não foi possível copiar um arquivo para o artefato.');
    }
}

/** @param string[] $command */
function run(array $command): void
{
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        fail('Não foi possível iniciar a verificação do pacote principal.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        fwrite(STDERR, $stdout . $stderr);
        fail('O pacote principal não foi aprovado antes da montagem do gate.');
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
        if (!$item->isFile() || $item->getFilename() === 'GATE-MANIFEST.json') {
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
