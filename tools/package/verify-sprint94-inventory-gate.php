<?php

/** Verifica topologia, hashes e entrypoints do gate temporario da Sprint 9.4. */

declare(strict_types=1);

const SPRINT94_INVENTORY_GATE_FORMAT = 'PCA_PNCP_SPRINT94_INVENTORY_GATE_V1';

$projectRoot = dirname(__DIR__, 2);
$artifact = $argv[1] ?? $projectRoot . '/build/pca-pncp-sprint94-inventory-gate';
$artifact = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $artifact), DIRECTORY_SEPARATOR);
$manifestPath = $artifact . DIRECTORY_SEPARATOR . 'GATE-MANIFEST.json';
if (!is_file($manifestPath)) {
    sprint94VerifyFail('Manifesto do gate nao encontrado.');
}
$manifest = json_decode((string) file_get_contents($manifestPath), true, 32, JSON_THROW_ON_ERROR);
if (!is_array($manifest) || ($manifest['formato'] ?? null) !== SPRINT94_INVENTORY_GATE_FORMAT) {
    sprint94VerifyFail('Formato do manifesto do gate invalido.');
}
$top = array_values(array_diff(scandir($artifact) ?: [], ['.', '..']));
sort($top, SORT_STRING);
if ($top !== ['GATE-MANIFEST.json', 'backend', 'gate']) {
    sprint94VerifyFail('O gate contem entradas de topo nao autorizadas.');
}
sprint94VerifyRun([PHP_BINARY, $projectRoot . '/tools/package/verify.php', $artifact . '/backend']);
$expected = $manifest['arquivos'] ?? null;
if (!is_array($expected) || sprint94VerifyHashes($artifact) !== $expected) {
    sprint94VerifyFail('Os arquivos do gate divergem do manifesto.');
}
$backendHash = hash_file('sha256', $artifact . '/backend/PACKAGE-MANIFEST.json');
if (!hash_equals((string) ($manifest['backend_manifest_sha256'] ?? ''), $backendHash)) {
    sprint94VerifyFail('O pacote principal diverge do manifesto do gate.');
}
foreach (['.git', '.agents', 'config', 'database', 'docs', 'tools'] as $forbidden) {
    if (file_exists($artifact . DIRECTORY_SEPARATOR . $forbidden)) {
        sprint94VerifyFail("O gate contem a entrada proibida {$forbidden}.");
    }
}
require $artifact . '/backend/vendor/autoload.php';
require $artifact . '/gate/bootstrap.php';
foreach (['executar_precheck_sprint_94', 'executar_inventario_sprint_94'] as $entrypoint) {
    if (!function_exists($entrypoint)) {
        sprint94VerifyFail("O entrypoint {$entrypoint} nao pode ser carregado.");
    }
}
echo json_encode([
    'resultado' => 'ARTEFATO_SPRINT94_VALIDADO',
    'diretorio' => $artifact,
    'arquivos_verificados' => count($expected),
    'configuracoes_ou_segredos' => false,
    'entrypoints_carregaveis' => true,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;

/** @param string[] $command */
function sprint94VerifyRun(array $command): void
{
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        sprint94VerifyFail('Nao foi possivel verificar o pacote principal.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        fwrite(STDERR, $stdout . $stderr);
        sprint94VerifyFail('O pacote principal incorporado nao foi aprovado.');
    }
}

/** @return array<string, string> */
function sprint94VerifyHashes(string $root): array
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

function sprint94VerifyFail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
