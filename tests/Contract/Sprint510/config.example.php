<?php

declare(strict_types=1);

/**
 * Modelo sem dados fisicos para a investigacao Oracle da Sprint 5.10.
 *
 * Copie para fora do repositorio e do webroot. Os SELECTs, binds e valores
 * esperados devem ser fornecidos a partir do DDL e da massa oficial validados
 * com o DBA. O probe nunca registra esses valores.
 */
return [
    'oracle' => [
        'allow_reads' => false,
        'dedicated_mass' => false,
        'official_sgi_workflow' => false,
        'physical_source_confirmed' => false,
        'modes' => [
            'metadata' => [[
                'label' => 'metadados_fonte',
                'sql' => 'SELECT ...',
                'params' => [],
                'expected_rows' => 0,
                'aliases' => ['TIPO', 'NULAVEL'],
                'assertions' => [],
            ]],
            'individual' => [[
                'label' => 'caso_individual',
                'sql' => 'SELECT ...',
                'params' => [],
                'expected_rows' => 0,
                'aliases' => ['JUSTIFICATIVA', 'VINCULO', 'ORDEM_EVENTO'],
                'assertions' => [],
            ]],
            'consolidado' => [[
                'label' => 'caso_consolidado',
                'sql' => 'SELECT ...',
                'params' => [],
                'expected_rows' => 0,
                'aliases' => ['JUSTIFICATIVA', 'VINCULO', 'ORDEM_EVENTO'],
                'assertions' => [],
            ]],
            'ausente' => [[
                'label' => 'caso_ausente',
                'sql' => 'SELECT ...',
                'params' => [],
                'expected_rows' => 0,
                'aliases' => ['JUSTIFICATIVA', 'VINCULO', 'ORDEM_EVENTO'],
                'assertions' => [],
            ]],
        ],
    ],
];
