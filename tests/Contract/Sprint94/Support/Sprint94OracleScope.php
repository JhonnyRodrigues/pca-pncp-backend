<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94\Support;

use Semae\Pca\Composition\PerfilPublicacaoPca;

final class Sprint94OracleScope
{
    /** @param array<string, mixed> $oracle @return string[] */
    public function expectedUasgs(array $oracle, PerfilPublicacaoPca $profile, int $year): array
    {
        $mapping = $profile->getConfiguracaoEstrategia();
        $values = [];
        foreach ($oracle['estrategia_corrente'] ?? [] as $row) {
            $key = $row['CHAVE_CONFIGURACAO'] ?? null;
            if (is_string($key)) {
                $values[$key][] = $row['VALOR_CONFIGURACAO'] ?? null;
            }
        }

        $strategyValues = $values[$mapping->getChaveEstrategia()] ?? [];
        if ($strategyValues === []) {
            throw $this->failure('ORACLE_ESTRATEGIA_AUSENTE');
        }
        if (count($strategyValues) !== 1) {
            throw $this->failure('ORACLE_ESTRATEGIA_DUPLICADA');
        }

        $strategy = $this->text($strategyValues[0]);
        if ($strategy === $mapping->getLiteralEstrategiaConsolidada()) {
            return [$this->consolidatingUasg($values, $mapping->getChaveUasgConsolidadora())];
        }
        if ($strategy !== $mapping->getLiteralEstrategiaIndividual()) {
            throw $this->failure('ORACLE_ESTRATEGIA_DESCONHECIDA');
        }

        $exercise = $oracle['exercicios'][(string) $year] ?? null;
        if (!is_array($exercise)) {
            throw $this->failure("ORACLE_EXERCICIO_{$year}_AUSENTE");
        }
        $units = $exercise['unidades'] ?? null;
        if (!is_array($units)) {
            throw $this->failure("ORACLE_UNIDADES_{$year}_INVALIDAS");
        }

        $uasgs = [];
        foreach ($units as $unit) {
            $uasg = is_array($unit) ? $this->text($unit['CODIGO_UASG'] ?? null) : null;
            if ($uasg === null) {
                throw $this->failure("ORACLE_UNIDADE_{$year}_SEM_UASG");
            }
            $uasgs[] = $uasg;
        }

        $uasgs = array_values(array_unique($uasgs));
        sort($uasgs, SORT_STRING);
        return $uasgs;
    }

    /** @param array<string, array<int, mixed>> $values */
    private function consolidatingUasg(array $values, string $key): string
    {
        $uasgValues = $values[$key] ?? [];
        if ($uasgValues === []) {
            throw $this->failure('ORACLE_UASG_CONSOLIDADORA_AUSENTE');
        }
        if (count($uasgValues) !== 1) {
            throw $this->failure('ORACLE_UASG_CONSOLIDADORA_DUPLICADA');
        }

        $uasg = $this->text($uasgValues[0]);
        if ($uasg === null) {
            throw $this->failure('ORACLE_UASG_CONSOLIDADORA_INVALIDA');
        }
        return $uasg;
    }

    private function text(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function failure(string $errorCode): Sprint94InventoryException
    {
        return new Sprint94InventoryException(
            $errorCode,
            'O escopo Oracle do inventario nao pode ser determinado com seguranca.'
        );
    }
}
