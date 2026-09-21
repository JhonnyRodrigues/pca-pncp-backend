<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85\Support;

final class Sprint85LogGateQueries
{
    /** @param array<string, mixed> $configuration */
    public function __construct(private readonly array $configuration) {}

    /** @return array<string, array<string, mixed>> */
    public function precheck(): array
    {
        return [
            'estrutura_log_tecnico' => [
                'sql' => 'SELECT c.COLUMN_NAME AS COLUMN_NAME, c.DATA_TYPE AS DATA_TYPE, '
                    . 'c.DATA_LENGTH AS DATA_LENGTH, c.CHAR_LENGTH AS CHAR_LENGTH '
                    . 'FROM ALL_TAB_COLUMNS c '
                    . "WHERE c.OWNER = 'SGI' AND c.TABLE_NAME = 'SEGURANCA_LOG' "
                    . "AND c.COLUMN_NAME IN ('ACTION', 'DESCRIPTION') "
                    . 'ORDER BY c.COLUMN_NAME',
                'binds' => [],
                'columns' => ['COLUMN_NAME', 'DATA_TYPE', 'DATA_LENGTH', 'CHAR_LENGTH'],
                'expected_cardinality' => ['exact' => 2],
            ],
            'logs_tecnicos_antes_gate' => $this->logQuery(0),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function verification(): array
    {
        return ['log_tecnico_persistido' => $this->logQuery(1)];
    }

    /** @return array<string, mixed> */
    private function logQuery(int $expected): array
    {
        return [
            'sql' => 'SELECT l.ACTION AS ACTION, l.DESCRIPTION AS DESCRIPTION '
                . 'FROM SGI.SEGURANCA_LOG l '
                . 'WHERE l.ACTION = ? AND DBMS_LOB.INSTR(l.DESCRIPTION, ?) > 0',
            'binds' => ['execute', $this->configuration['identificador_log']],
            'columns' => ['ACTION', 'DESCRIPTION'],
            'expected_cardinality' => ['exact' => $expected],
        ];
    }
}
