<?php

/** Verifica a integridade e o escopo do artefato corretivo 3A da Sprint 9.4. */

declare(strict_types=1);

const SPRINT94_CORRECTION_3A_FORMAT = 'PCA_PNCP_SPRINT94_CORRECAO_3A_V1';

$projectRoot = dirname(__DIR__, 2);
$artifact = $argv[1] ?? $projectRoot . '/build/pca-pncp-sprint94-correcao-3a';
$artifact = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $artifact), DIRECTORY_SEPARATOR);
$manifestPath = $artifact . DIRECTORY_SEPARATOR . 'CORRECAO-MANIFEST.json';

if (!is_file($manifestPath)) {
    sprint94CorrectionVerifyFail('Manifesto do artefato corretivo nao encontrado.');
}

$manifest = json_decode((string) file_get_contents($manifestPath), true, 32, JSON_THROW_ON_ERROR);
if (!is_array($manifest) || ($manifest['formato'] ?? null) !== SPRINT94_CORRECTION_3A_FORMAT) {
    sprint94CorrectionVerifyFail('Formato do manifesto corretivo invalido.');
}
if (($manifest['release_oficial'] ?? null) !== false) {
    sprint94CorrectionVerifyFail('O artefato corretivo nao pode se declarar release oficial.');
}
if (($manifest['configuracoes_ou_segredos'] ?? null) !== false) {
    sprint94CorrectionVerifyFail('O artefato corretivo declarou configuracao ou segredo.');
}

$top = array_values(array_diff(scandir($artifact) ?: [], ['.', '..']));
sort($top, SORT_STRING);
if ($top !== ['CORRECAO-MANIFEST.json', 'pca-pncp-backend', 'scriptcase']) {
    sprint94CorrectionVerifyFail('O artefato corretivo contem entradas de topo nao autorizadas.');
}

sprint94CorrectionVerifyRun([
    PHP_BINARY,
    $projectRoot . '/tools/package/verify.php',
    $artifact . '/pca-pncp-backend',
]);

$blankRelative = 'scriptcase/PCA_PUBLICAR_B/onExecute.php';
$found = sprint94CorrectionVerifyFileHashes($artifact);
$expected = $manifest['arquivos'] ?? null;
if (!is_array($expected) || $found !== $expected) {
    sprint94CorrectionVerifyFail('Os arquivos do artefato divergem do manifesto.');
}
foreach (array_keys($found) as $relative) {
    if ($relative !== $blankRelative && !str_starts_with($relative, 'pca-pncp-backend/')) {
        sprint94CorrectionVerifyFail('O artefato contem arquivo fora do escopo corretivo.');
    }
}

$backendManifest = $artifact . '/pca-pncp-backend/PACKAGE-MANIFEST.json';
if (!hash_equals(
    (string) ($manifest['backend_manifest_sha256'] ?? ''),
    (string) hash_file('sha256', $backendManifest)
)) {
    sprint94CorrectionVerifyFail('O manifesto do backend diverge do artefato corretivo.');
}

$blank = $artifact . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $blankRelative);
$blankHash = hash_file('sha256', $blank);
if (!is_string($blankHash) || !hash_equals((string) ($manifest['blank_sha256'] ?? ''), $blankHash)) {
    sprint94CorrectionVerifyFail('A fonte da Blank diverge do manifesto corretivo.');
}
$blankCode = (string) file_get_contents($blank);
if (preg_match('/array_map\s*\(\s*[\'\"]pca_publicar_serializar_resultado_unidade[\'\"]/', $blankCode) === 1) {
    sprint94CorrectionVerifyFail('A Blank ainda depende do callback textual incompatível.');
}
if (!str_contains($blankCode, 'foreach ($resultado->getResultadosUnitarios() as $resultadoUnitario)')) {
    sprint94CorrectionVerifyFail('A serializacao segura da Blank nao foi encontrada.');
}
if (!str_contains($blankCode, '$resultadoSerializado = pca_publicar_serializar_resultado(')) {
    sprint94CorrectionVerifyFail('A preservacao antecipada do resultado nao foi encontrada.');
}

$finalizer = (string) file_get_contents(
    $artifact . '/pca-pncp-backend/src/Application/UseCases/PublicarPca/'
        . 'FinalizadorProcessamentoUnidadeInterno.php'
);
if (!str_contains(
    $finalizer,
    "'decisao' => \$contexto->getResultadoDecisao()->getDecisao()->value"
)) {
    sprint94CorrectionVerifyFail('O contexto da decisao nao foi encontrado no backend.');
}

echo json_encode(
    [
        'resultado' => 'CORRECAO_SPRINT94_3A_VALIDADA',
        'diretorio' => $artifact,
        'release_oficial' => false,
        'arquivos_verificados' => count($found),
        'backend_validado' => true,
        'blank_validada' => true,
        'configuracoes_ou_segredos' => false,
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
), PHP_EOL;

/** @param string[] $command */
function sprint94CorrectionVerifyRun(array $command): void
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        sprint94CorrectionVerifyFail('Nao foi possivel executar uma etapa de verificacao.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        fwrite(STDERR, $stdout . $stderr);
        sprint94CorrectionVerifyFail('A verificacao do backend falhou.');
    }
}

/** @return array<string, string> */
function sprint94CorrectionVerifyFileHashes(string $root): array
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

function sprint94CorrectionVerifyFail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
