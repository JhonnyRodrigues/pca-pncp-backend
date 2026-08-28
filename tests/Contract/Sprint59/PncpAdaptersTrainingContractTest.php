<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint59;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Contract\Sprint59\Support\PncpAdaptersContractRunner;
use Tests\Contract\Sprint59\Support\SafeContractReport;
use Tests\Contract\Sprint59\Support\Sprint59Config;

#[Group('pncp-live')]
final class PncpAdaptersTrainingContractTest extends TestCase
{
    public function testAdaptersReaisDevemCumprirCicloCompletoNoTreinamento(): void
    {
        $config = Sprint59Config::loadFromEnvironment();
        $report = PncpAdaptersContractRunner::fromConfig($config)->run();
        $pncp = Sprint59Config::pncp($config);
        $json = SafeContractReport::encode($report, [
            $pncp['login'],
            $pncp['senha'],
            $pncp['cnpj'],
            $pncp['codigo_uasg'],
            $pncp['descricao_uasg'],
        ]);

        fwrite(STDOUT, PHP_EOL . $json . PHP_EOL . 'SHA256: ' . hash('sha256', $json) . PHP_EOL);

        $this->assertTrue($report['confirmado']);
        $this->assertTrue($report['tls_confirmado']);
        $this->assertTrue($report['limpeza_confirmada']);
        $this->assertTrue($report['eventos_sanitizados']);
    }
}
