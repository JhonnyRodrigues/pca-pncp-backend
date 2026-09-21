<?php

/**
 * Confere topologia, hashes, conteúdo permitido e bootstrap do gate de logs.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$artifact = $argv[1] ?? $projectRoot . '/build/pca-pncp-log-gate';
$artifact = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $artifact), DIRECTORY_SEPARATOR);
$manifestPath = $artifact . DIRECTORY_SEPARATOR . 'GATE-MANIFEST.json';

if (!is_file($manifestPath)) {
    fail('Manifesto do gate não encontrado.');
}

$manifest = json_decode((string) file_get_contents($manifestPath), true, 32, JSON_THROW_ON_ERROR);
if (!is_array($manifest) || ($manifest['formato'] ?? null) !== 'PCA_PNCP_LOG_GATE_V1') {
    fail('Formato do manifesto do gate inválido.');
}

$expectedTopLevel = ['GATE-MANIFEST.json', 'backend', 'gate'];
$foundTopLevel = array_values(array_diff(scandir($artifact) ?: [], ['.', '..']));
sort($expectedTopLevel, SORT_STRING);
sort($foundTopLevel, SORT_STRING);
if ($foundTopLevel !== $expectedTopLevel) {
    fail('O artefato do gate contém entradas de topo não autorizadas.');
}

run([
    PHP_BINARY,
    $projectRoot . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'package'
        . DIRECTORY_SEPARATOR . 'verify.php',
    $artifact . DIRECTORY_SEPARATOR . 'backend',
]);

$expected = $manifest['arquivos'] ?? null;
if (!is_array($expected) || fileHashes($artifact) !== $expected) {
    fail('Os arquivos do gate divergem do manifesto.');
}

$backendManifestHash = hash_file(
    'sha256',
    $artifact . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'PACKAGE-MANIFEST.json'
);
if (!hash_equals((string) ($manifest['backend_manifest_sha256'] ?? ''), $backendManifestHash)) {
    fail('O pacote principal do gate diverge do manifesto registrado.');
}

foreach (['.git', '.agents', 'docs', 'config', 'database', 'tools'] as $forbidden) {
    if (file_exists($artifact . DIRECTORY_SEPARATOR . $forbidden)) {
        fail("O artefato contém a entrada proibida {$forbidden}.");
    }
}

require $artifact . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR
    . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require $artifact . DIRECTORY_SEPARATOR . 'gate' . DIRECTORY_SEPARATOR . 'bootstrap.php';

foreach ([
    'executar_precheck_logs_sprint_85',
    'preparar_log_sintetico_sprint_85',
    'persistir_log_sintetico_sprint_85',
    'verificar_log_sintetico_sprint_85',
] as $entrypoint) {
    if (!function_exists($entrypoint)) {
        fail("O entrypoint {$entrypoint} não pode ser carregado.");
    }
}

echo json_encode(
    [
        'resultado' => 'ARTEFATO_GATE_VALIDADO',
        'diretorio' => $artifact,
        'arquivos_verificados' => count($expected),
        'arquivos_totais' => count($expected) + 1,
        'configuracoes_ou_segredos' => false,
        'entrypoints_carregaveis' => true,
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
), PHP_EOL;

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
        fail('O pacote principal incorporado não foi aprovado.');
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
