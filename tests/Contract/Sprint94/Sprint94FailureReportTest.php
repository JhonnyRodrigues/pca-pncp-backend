<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Contract\Sprint94\Support\Sprint94InventoryException;

require_once __DIR__ . '/scriptcase_inventory_probe.php';

final class Sprint94FailureReportTest extends TestCase
{
    public function testFalhaDeInventarioDeveExporSomenteCodigoSeguroDaEtapa(): void
    {
        $failure = new Sprint94InventoryException(
            'PNCP_AUTENTICACAO_INCONCLUSIVA',
            'detalhe interno que nao pode sair no relatorio',
            new RuntimeException('segredo acidental')
        );

        $report = \sprint94_inventory_failure($failure, 'INVENTARIO_INCONCLUSIVO');

        $this->assertSame('PNCP_AUTENTICACAO_INCONCLUSIVA', $report['codigo']);
        $serialized = json_encode($report, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('detalhe interno', $serialized);
        $this->assertStringNotContainsString('segredo acidental', $serialized);
    }
}
