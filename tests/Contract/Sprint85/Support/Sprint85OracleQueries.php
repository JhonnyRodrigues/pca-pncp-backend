<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint85\Support;

use Semae\Pca\Composition\PerfilPublicacaoPca;
use Tests\Contract\Sprint84\Support\OracleCheckpointQueries;

final class Sprint85OracleQueries
{
    /** @param array<string, mixed> $configuration */
    public function __construct(
        private readonly PerfilPublicacaoPca $profile,
        private readonly array $configuration,
        private readonly string $authenticatedLogin
    ) {}

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $queries = (new OracleCheckpointQueries(
            $this->profile,
            $this->configuration,
            $this->authenticatedLogin
        ))->all();

        $queries['controles_concorrencia']['expected_cardinality'] = ['exact' => 2];

        $queries['logs_tecnicos_gate'] = [
            'sql' => 'SELECT l.ACTION AS ACTION, l.DESCRIPTION AS DESCRIPTION '
                . 'FROM SGI.SEGURANCA_LOG l '
                . 'WHERE l.ACTION = ? AND DBMS_LOB.INSTR(l.DESCRIPTION, ?) > 0',
            'binds' => ['execute', $this->configuration['identificador_log']],
            'columns' => ['ACTION', 'DESCRIPTION'],
            'expected_cardinality' => [
                'exact' => $this->configuration['quantidade_logs_esperada'],
            ],
        ];

        return $queries;
    }
}
