<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Contract\Sprint94\Support\Sprint94Config;
use Tests\Contract\Sprint94\Support\Sprint94ConfigurationException;

final class Sprint94ConfigTest extends TestCase
{
    public function testConfiguracaoModeloDeveComecarDesarmadaParaProducao(): void
    {
        $configuracao = require __DIR__ . '/config.example.php';

        $validada = Sprint94Config::validate($configuracao);

        $this->assertSame('sgi.semaepiracicaba.sp.gov.br', $validada['server_name']);
        $this->assertSame('jsilva', $validada['login_autorizado']);
        $this->assertSame([2026, 2027], $validada['anos']);
        $this->assertSame('PRECHECK', $validada['etapa_autorizada']);
        $this->assertFalse($validada['allow_oracle_writes']);
        $this->assertFalse($validada['allow_pncp_writes']);
        $this->assertFalse($validada['allow_log_writes']);
    }

    /** @param callable(array<string, mixed>): void $alteracao */
    #[DataProvider('configuracoesInvalidas')]
    public function testDeveRecusarConfiguracaoQueAmplieOEscopo(callable $alteracao): void
    {
        $configuracao = require __DIR__ . '/config.example.php';
        $alteracao($configuracao);

        $this->expectException(Sprint94ConfigurationException::class);
        Sprint94Config::validate($configuracao);
    }

    /** @return iterable<string, array{callable(array<string, mixed>): void}> */
    public static function configuracoesInvalidas(): iterable
    {
        yield 'escrita Oracle' => [static function (array &$configuracao): void {
            $configuracao['allow_oracle_writes'] = true;
        }];
        yield 'escrita PNCP' => [static function (array &$configuracao): void {
            $configuracao['allow_pncp_writes'] = true;
        }];
        yield 'logs persistidos' => [static function (array &$configuracao): void {
            $configuracao['allow_log_writes'] = true;
        }];
        yield 'ano diferente' => [static function (array &$configuracao): void {
            $configuracao['anos'] = [2026];
        }];
        yield 'etapa mutante' => [static function (array &$configuracao): void {
            $configuracao['etapa_autorizada'] = 'MIGRACAO';
        }];
    }

}
