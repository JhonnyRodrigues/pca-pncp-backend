<?php

/** Monta o gate temporario e separado de inventario da Sprint 9.4. */

declare(strict_types=1);

const SPRINT94_INVENTORY_GATE_FORMAT = 'PCA_PNCP_SPRINT94_INVENTORY_GATE_V1';

$projectRoot = dirname(__DIR__, 2);
$buildRoot = $projectRoot . DIRECTORY_SEPARATOR . 'build';
$backendPackage = $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-backend';
$output = sprint94NormalizePath(
    $argv[1] ?? $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-sprint94-inventory-gate'
);
$allowedRoot = sprint94NormalizePath($buildRoot) . DIRECTORY_SEPARATOR;
if (!str_starts_with($output . DIRECTORY_SEPARATOR, $allowedRoot)) {
    sprint94Fail('O gate deve ser gerado dentro do diretorio build do projeto.');
}

sprint94Run([PHP_BINARY, $projectRoot . '/tools/package/build.php']);
sprint94Run([PHP_BINARY, $projectRoot . '/tools/package/verify.php', $backendPackage]);
sprint94RemoveTree($output);
sprint94MakeDirectory($output);
sprint94CopyTree($backendPackage, $output . DIRECTORY_SEPARATOR . 'backend');

$runtimeFiles = [
    'tests/Contract/Sprint94/Support/Sprint94Config.php',
    'tests/Contract/Sprint94/Support/Sprint94ConfigurationException.php',
    'tests/Contract/Sprint94/Support/Sprint94InventoryGate.php',
    'tests/Contract/Sprint94/Support/Sprint94InventoryException.php',
    'tests/Contract/Sprint94/Support/Sprint94OracleInventory.php',
    'tests/Contract/Sprint94/Support/Sprint94OracleScope.php',
    'tests/Contract/Sprint94/Support/Sprint94PncpInventoryReader.php',
    'tests/Contract/Sprint94/Support/Sprint94PncpReadOnlyTransport.php',
    'tests/Contract/Sprint94/Support/Sprint94ReadOnlyAdodbGuard.php',
    'tests/Contract/Sprint94/Support/Sprint94Report.php',
    'tests/Contract/Sprint94/scriptcase_inventory_probe.php',
];
foreach ($runtimeFiles as $relative) {
    sprint94CopyFile(
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

require_once __DIR__ . '/tests/Contract/Sprint94/scriptcase_inventory_probe.php';
PHP;
sprint94MakeDirectory($output . DIRECTORY_SEPARATOR . 'gate');
file_put_contents($output . DIRECTORY_SEPARATOR . 'gate' . DIRECTORY_SEPARATOR . 'bootstrap.php', $bootstrap . PHP_EOL);

$manifest = [
    'formato' => SPRINT94_INVENTORY_GATE_FORMAT,
    'php_minimo' => '8.1',
    'finalidade' => 'Inventario temporario somente leitura da Sprint 9.4',
    'configuracoes_ou_segredos' => false,
    'backend_manifest_sha256' => hash_file('sha256', $output . '/backend/PACKAGE-MANIFEST.json'),
    'arquivos' => sprint94FileHashes($output),
];
file_put_contents(
    $output . '/GATE-MANIFEST.json',
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL
);
echo json_encode([
    'resultado' => 'ARTEFATO_SPRINT94_GERADO',
    'diretorio' => $output,
    'arquivos_verificados' => count($manifest['arquivos']),
    'configuracoes_ou_segredos' => false,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;

function sprint94NormalizePath(string $path): string
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

function sprint94MakeDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        sprint94Fail('Nao foi possivel criar diretorio do gate.');
    }
}

function sprint94RemoveTree(string $path): void
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

function sprint94CopyTree(string $source, string $destination): void
{
    sprint94MakeDirectory($destination);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($source) + 1);
        $target = $destination . DIRECTORY_SEPARATOR . $relative;
        $item->isDir() ? sprint94MakeDirectory($target) : sprint94CopyFile($item->getPathname(), $target);
    }
}

function sprint94CopyFile(string $source, string $destination): void
{
    if (!is_file($source)) {
        sprint94Fail("Arquivo necessario ausente: {$source}");
    }
    sprint94MakeDirectory(dirname($destination));
    if (!copy($source, $destination)) {
        sprint94Fail('Nao foi possivel copiar arquivo do gate.');
    }
}

/** @param string[] $command */
function sprint94Run(array $command): void
{
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        sprint94Fail('Nao foi possivel validar o pacote principal.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        fwrite(STDERR, $stdout . $stderr);
        sprint94Fail('O pacote principal nao foi aprovado.');
    }
}

/** @return array<string, string> */
function sprint94FileHashes(string $root): array
{
    $hashes = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
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

function sprint94Fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
