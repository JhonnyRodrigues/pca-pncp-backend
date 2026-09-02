<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint82;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Contract\Sprint82\Support\OracleContractRunner;
use Tests\Support\PerfilPublicacaoPcaFixture;

final class OracleContractRunnerTest extends TestCase
{
    private string|false $previousRegistry;
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->previousRegistry = getenv('PCA_PNCP_PROFILE_REGISTRY_FILE');
        $base = tempnam(sys_get_temp_dir(), 'pca-s82-runner-');

        if ($base === false || !unlink($base) || !mkdir($base)) {
            throw new RuntimeException('Nao foi possivel preparar o teste do runner 8.2.');
        }

        $this->temporaryDirectory = $base;
        $profilePath = $this->write('profile.php', PerfilPublicacaoPcaFixture::valido());
        $registryPath = $this->write('registry.php', [
            'pca.homologacao.test' => $profilePath,
        ]);
        putenv('PCA_PNCP_PROFILE_REGISTRY_FILE=' . $registryPath);
    }

    protected function tearDown(): void
    {
        $this->previousRegistry === false
            ? putenv('PCA_PNCP_PROFILE_REGISTRY_FILE')
            : putenv('PCA_PNCP_PROFILE_REGISTRY_FILE=' . $this->previousRegistry);

        foreach (glob($this->temporaryDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->temporaryDirectory);
    }

    public function testDeveMontarRootSemIoEExecutarCommitPelaComposicaoDeProducao(): void
    {
        $report = OracleContractRunner::fromConfig(
            new AdodbCommitRunnerSprint82(),
            'usuario.teste',
            'pca.homologacao.test',
            $this->config()
        )->run('commit');

        $this->assertTrue($report['confirmado']);
        $this->assertTrue(
            $report['verificacoes']['composition_root_montado_sem_io']
        );
        $this->assertTrue(
            $report['verificacoes']['commit_controlado_concluido']
        );
        $this->assertFalse(
            $report['verificacoes']['alteracao_negocio_executada']
        );
        $this->assertFalse($report['verificacoes']['close_invocado']);
    }

    /** @return array<string, mixed> */
    private function config(): array
    {
        return [
            'allow_writes' => true,
            'server_name' => 'pca.homologacao.test',
            'massa' => [
                'ano' => (int) date('Y') + 1,
                'id_unidade_orcamentaria' => 10,
                'sequencial_teste' => 82,
                'mensagem_auditoria' => 'PCA-S82-INTEGRACAO-ORACLE',
                'lock_hold_seconds' => 1,
            ],
        ];
    }

    /** @param mixed $value */
    private function write(string $name, mixed $value): string
    {
        $path = $this->temporaryDirectory . DIRECTORY_SEPARATOR . $name;
        $contents = "<?php\n\nreturn " . var_export($value, true) . ";\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Nao foi possivel escrever fixture externa.');
        }

        return $path;
    }
}

final class AdodbCommitRunnerSprint82
{
    public function BeginTrans(): bool
    {
        return true;
    }

    public function CommitTrans(): bool
    {
        return true;
    }

    public function RollbackTrans(): bool
    {
        return true;
    }

    /** @param array<string, mixed> $parameters */
    public function Execute(string $sql, array $parameters = []): bool
    {
        return true;
    }

    /** @param array<string, mixed> $parameters @return array<int, array<string, mixed>> */
    public function GetAll(string $sql, array $parameters = []): array
    {
        if (str_contains($sql, "SYS_CONTEXT('USERENV', 'SID')")) {
            return [['SESSAO' => '82']];
        }

        if (str_contains($sql, 'FOR UPDATE NOWAIT')) {
            return [['ESCOPO' => 'ESTRATEGIA_ANUAL']];
        }

        return [];
    }

    public function Param(string|false $name): string
    {
        return ':' . ($name === false ? 'param' : $name);
    }

    public function ErrorNo(): int
    {
        return 0;
    }

    public function ErrorMsg(): string
    {
        return '';
    }
}
