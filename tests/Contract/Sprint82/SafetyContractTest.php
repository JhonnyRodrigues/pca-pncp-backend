<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint82;

use PHPUnit\Framework\TestCase;

final class SafetyContractTest extends TestCase
{
    public function testEntrypointDevePreservarAssinaturaAprovada(): void
    {
        require_once __DIR__ . '/scriptcase_probe.php';
        $funcao = new \ReflectionFunction('executar_integracao_oracle_sprint_82');

        $this->assertSame(
            ['db', 'login', 'serverName', 'mode'],
            array_map(
                static fn (\ReflectionParameter $parametro): string => $parametro->getName(),
                $funcao->getParameters()
            )
        );
        $this->assertSame('adapters', $funcao->getParameters()[3]->getDefaultValue());
    }

    public function testEntrypointDeveFalharFechadoSemConfiguracao(): void
    {
        require_once __DIR__ . '/scriptcase_probe.php';
        $anterior = getenv('PCA_SPRINT82_CONFIG_FILE');
        putenv('PCA_SPRINT82_CONFIG_FILE');

        try {
            $relatorio = \executar_integracao_oracle_sprint_82(
                new \stdClass(),
                'usuario-secreto',
                'servidor-secreto'
            );
        } finally {
            $anterior === false
                ? putenv('PCA_SPRINT82_CONFIG_FILE')
                : putenv('PCA_SPRINT82_CONFIG_FILE=' . $anterior);
        }

        $this->assertFalse($relatorio['confirmado']);
        $this->assertSame('preparacao', $relatorio['falha']['etapa']);
        $json = json_encode($relatorio, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('usuario-secreto', $json);
        $this->assertStringNotContainsString('servidor-secreto', $json);
    }
}
