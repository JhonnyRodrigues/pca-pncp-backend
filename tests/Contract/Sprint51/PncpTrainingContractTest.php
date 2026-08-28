<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint51;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Contract\Sprint51\Support\PncpContractRunner;
use Tests\Contract\Sprint51\Support\Sprint51Config;

#[Group('pncp-live')]
final class PncpTrainingContractTest extends TestCase
{
    public function testFluxoCompletoNoAmbienteDeTreinamento(): void
    {
        $config = Sprint51Config::loadFromEnvironment();
        $report = PncpContractRunner::fromConfig($config)->run();
        $json = json_encode(
            $report,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        fwrite(STDOUT, "\nSPRINT51_PNCP_EVIDENCE\n{$json}\n");

        $this->assertTrue(
            $report['confirmado'] ?? false,
            'Gate PNCP não foi integralmente confirmado; consulte o relatório sanitizado acima.'
        );
    }
}
