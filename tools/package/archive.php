<?php

/**
 * Compacta o pacote validado, identifica a release pelo commit e calcula seu SHA-256.
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    exit(packageArchiveMain());
}

function packageArchiveMain(): int
{
    try {
        $projectRoot = dirname(__DIR__, 2);
        $buildRoot = $projectRoot . DIRECTORY_SEPARATOR . 'build';
        $package = $buildRoot . DIRECTORY_SEPARATOR . 'pca-pncp-backend';

        packageArchiveAssertCleanWorktree($projectRoot);
        $commitId = packageArchiveCommitId($projectRoot);
        $generatedAt = new \DateTimeImmutable('now');
        $date = packageArchiveDate($generatedAt);
        $archive = $buildRoot . DIRECTORY_SEPARATOR
            . packageArchiveFileName($date, $commitId);
        $result = packageArchiveCreate($package, $archive);

        echo json_encode(
            [
                'resultado' => 'RELEASE_ARQUIVADA',
                'gerado_em' => $generatedAt->format(DATE_ATOM),
                'commit' => $commitId,
                'arquivo' => $archive,
                'tamanho_bytes' => $result['tamanho_bytes'],
                'sha256' => $result['sha256'],
                'entradas' => $result['entradas'],
                'topologia_segura' => true,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ), PHP_EOL;

        return 0;
    } catch (\Throwable $exception) {
        fwrite(STDERR, $exception->getMessage() . PHP_EOL);

        return 1;
    }
}

function packageArchiveDate(?\DateTimeImmutable $generatedAt = null): string
{
    return ($generatedAt ?? new \DateTimeImmutable('now'))->format('Ymd');
}

function packageArchiveFileName(string $date, string $commitId): string
{
    if (preg_match('/^[0-9]{8}$/D', $date) !== 1) {
        throw new \RuntimeException('A data da release é inválida.');
    }
    if (preg_match('/^[0-9a-f]{7}$/D', $commitId) !== 1) {
        throw new \RuntimeException('O commit da release é inválido.');
    }

    return "pca-pncp-backend_{$date}_{$commitId}.tar.gz";
}

function packageArchiveAssertCleanWorktree(string $projectRoot): void
{
    $status = packageArchiveRun([
        'git',
        '-C',
        $projectRoot,
        'status',
        '--porcelain',
        '--untracked-files=normal',
    ]);

    if (trim($status) !== '') {
        throw new \RuntimeException(
            'A release não pode ser criada com alterações não commitadas.'
        );
    }
}

function packageArchiveCommitId(string $projectRoot): string
{
    $commitId = trim(packageArchiveRun([
        'git',
        '-C',
        $projectRoot,
        'rev-parse',
        '--short=7',
        'HEAD',
    ]));

    if (preg_match('/^[0-9a-f]{7}$/D', $commitId) !== 1) {
        throw new \RuntimeException('Não foi possível identificar o commit da release.');
    }

    return $commitId;
}

/** @return array{tamanho_bytes: int, sha256: string, entradas: int} */
function packageArchiveCreate(string $package, string $archive): array
{
    $package = rtrim($package, '/\\');

    if (!is_dir($package)) {
        throw new \RuntimeException('Pacote não encontrado. Execute package:build primeiro.');
    }
    if (!is_file($package . DIRECTORY_SEPARATOR . 'PACKAGE-MANIFEST.json')) {
        throw new \RuntimeException('Manifesto do pacote não encontrado.');
    }
    if (is_file($archive)) {
        throw new \RuntimeException('O arquivo da release já existe.');
    }

    try {
        packageArchiveRun([
            packageArchiveTarBinary(),
            '-czf',
            $archive,
            '-C',
            dirname($package),
            basename($package),
        ]);

        $entries = packageArchiveListEntries($archive);
        if ($entries === []) {
            throw new \RuntimeException('O arquivo da release está vazio.');
        }

        $root = preg_quote(basename($package), '#');
        foreach ($entries as $entry) {
            if (
                preg_match("#^{$root}(?:/|$)#D", $entry) !== 1
                || preg_match('#(^|/)\.\.(?:/|$)#D', $entry) === 1
            ) {
                throw new \RuntimeException('O arquivo da release possui topologia insegura.');
            }
        }

        $size = filesize($archive);
        $hash = hash_file('sha256', $archive);
        if (!is_int($size) || $size <= 0 || !is_string($hash)) {
            throw new \RuntimeException('Não foi possível medir a release criada.');
        }

        return [
            'tamanho_bytes' => $size,
            'sha256' => $hash,
            'entradas' => count($entries),
        ];
    } catch (\Throwable $exception) {
        if (is_file($archive)) {
            unlink($archive);
        }

        throw $exception;
    }
}

/** @return string[] */
function packageArchiveListEntries(string $archive): array
{
    $output = packageArchiveRun([
        packageArchiveTarBinary(),
        '-tzf',
        $archive,
    ]);

    return array_values(array_filter(
        preg_split('/\R/', trim($output)) ?: [],
        static fn (string $entry): bool => $entry !== ''
    ));
}

function packageArchiveTarBinary(): string
{
    return PHP_OS_FAMILY === 'Windows' ? 'tar.exe' : 'tar';
}

/** @param string[] $command */
function packageArchiveRun(array $command): string
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new \RuntimeException('Não foi possível iniciar uma etapa da release.');
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new \RuntimeException(trim($stdout . $stderr)
            ?: 'Uma etapa da release falhou.');
    }

    return $stdout;
}
