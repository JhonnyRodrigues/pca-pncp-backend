<?php

/**
 * Recebe um pacote já construído e confere estrutura, hashes, conteúdo proibido e carregamento da fachada.
 */

declare(strict_types=1);

$package = $argv[1] ?? dirname(__DIR__, 2) . '/build/pca-pncp-backend';
$package = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $package), DIRECTORY_SEPARATOR);
$manifestPath = $package . DIRECTORY_SEPARATOR . 'PACKAGE-MANIFEST.json';

if (!is_file($manifestPath)) {
    fail('Manifesto do pacote não encontrado.');
}

$manifest = json_decode((string) file_get_contents($manifestPath), true, 32, JSON_THROW_ON_ERROR);
if (!is_array($manifest) || ($manifest['formato'] ?? null) !== 'PCA_PNCP_BACKEND_V1') {
    fail('Formato do manifesto inválido.');
}

$allowedTopLevel = ['PACKAGE-MANIFEST.json', 'src', 'vendor'];
$foundTopLevel = array_values(array_diff(scandir($package) ?: [], ['.', '..']));
sort($allowedTopLevel, SORT_STRING);
sort($foundTopLevel, SORT_STRING);
if ($foundTopLevel !== $allowedTopLevel) {
    fail('O pacote contém entradas não autorizadas.');
}

foreach (['tests', 'integracao', 'config', '.git', '.agents'] as $forbidden) {
    if (file_exists($package . DIRECTORY_SEPARATOR . $forbidden)) {
        fail("O pacote contém o diretório proibido {$forbidden}.");
    }
}

foreach (['phpunit', 'sebastian', 'phar-io', 'theseer'] as $developmentVendor) {
    if (is_dir($package . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $developmentVendor)) {
        fail("O pacote contém a dependência de desenvolvimento {$developmentVendor}.");
    }
}

$expected = $manifest['arquivos'] ?? null;
if (!is_array($expected)) {
    fail('Lista de hashes ausente do manifesto.');
}

$found = fileHashes($package);
if ($found !== $expected) {
    fail('Os arquivos do pacote divergem do manifesto.');
}

$lockHash = hash_file('sha256', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'composer.lock');
if (!hash_equals((string) ($manifest['composer_lock_sha256'] ?? ''), $lockHash)) {
    fail('O pacote não foi gerado a partir do composer.lock corrente.');
}

require $package . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (!class_exists('Semae\\Pca\\PublicadorPcaScriptcase')) {
    fail('A fachada pública não pode ser carregada pelo pacote.');
}
if (!class_exists('Semae\\Pca\\RegistradorEventosTecnicosScriptcase')) {
    fail('O registrador público não pode ser carregado pelo pacote.');
}

echo json_encode(
    [
        'resultado' => 'PACOTE_VALIDADO',
        'diretorio' => $package,
        'arquivos_verificados' => count($found),
        'arquivos_totais' => count($found) + 1,
        'dependencias_desenvolvimento' => false,
        'configuracoes_ou_segredos' => false,
        'fachada_carregavel' => true,
        'registrador_carregavel' => true,
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
), PHP_EOL;

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
