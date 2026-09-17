<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use Semae\Pca\Infrastructure\Oracle\Adodb\ConversorCharsetAdodbOracle;
use Throwable;

final class OracleCheckpointExecutor
{
    private readonly ConversorCharsetAdodbOracle $conversorCharset;

    public function __construct(
        private readonly Sprint84AdodbGuard $adodb,
        string $charsetAdodb = 'UTF-8'
    ) {
        $this->conversorCharset = new ConversorCharsetAdodbOracle($charsetAdodb);
    }

    /**
     * @param array<string, array<string, mixed>> $queries
     * @return array<string, mixed>
     */
    public function execute(array $queries, bool $validateContract = true): array
    {
        $results = [];
        $diagnostics = [];

        foreach ($queries as $name => $query) {
            [$preparedSql, $preparedBinds] = $this->prepareBinds(
                $name,
                $query['sql'],
                $query['binds']
            );

            try {
                $rows = $this->adodb->GetAll($preparedSql, $preparedBinds);
            } catch (Throwable $failure) {
                throw new Sprint84GateException(
                    'PRECHECK_CONSULTA_FALHOU',
                    'PRECHECK_ORACLE',
                    "A consulta Oracle {$name} falhou: {$failure->getMessage()}",
                    [
                        'consulta' => $name,
                        'sql' => $query['sql'],
                        'binds' => $query['binds'],
                        'sql_preparado' => $preparedSql,
                        'binds_preparados' => $preparedBinds,
                    ],
                    $failure
                );
            }

            if ($rows === false) {
                throw new Sprint84GateException(
                    'PRECHECK_CONSULTA_FALHOU',
                    'PRECHECK_ORACLE',
                    "A consulta Oracle {$name} foi recusada pelo ADOdb.",
                    [
                        'consulta' => $name,
                        'sql' => $query['sql'],
                        'binds' => $query['binds'],
                        'sql_preparado' => $preparedSql,
                        'binds_preparados' => $preparedBinds,
                        'erro_adodb' => [
                            'numero' => $this->adodb->ErrorNo(),
                            'mensagem' => $this->adodb->ErrorMsg(),
                        ],
                    ]
                );
            }

            try {
                $rows = $this->conversorCharset->converterResultadoGetAll($rows);
            } catch (Throwable $failure) {
                throw new Sprint84GateException(
                    'PRECHECK_CHARSET_INVALIDO',
                    'PRECHECK_ORACLE',
                    "A consulta Oracle {$name} retornou texto incompatível com o charset configurado.",
                    [
                        'consulta' => $name,
                        'charset_adodb' => $this->conversorCharset->getCharsetAdodb(),
                        'charset_destino' => 'UTF-8',
                    ],
                    $failure
                );
            }

            if (!is_array($rows) || !array_is_list($rows)) {
                throw new Sprint84GateException(
                    'PRECHECK_RETORNO_INVALIDO',
                    'PRECHECK_ORACLE',
                    "A consulta Oracle {$name} não retornou uma lista de linhas.",
                    ['consulta' => $name, 'tipo' => get_debug_type($rows), 'valor' => $rows]
                );
            }

            if ($validateContract) {
                $this->validateCardinality($name, $rows, $query['expected_cardinality']);
                $this->validateColumns($name, $rows, $query['columns']);
            }

            $results[$name] = $rows;
            $diagnostics[$name] = [
                'sql' => $query['sql'],
                'binds' => $query['binds'],
                'colunas_esperadas' => $query['columns'],
                'cardinalidade_esperada' => $query['expected_cardinality'],
                'cardinalidade_encontrada' => count($rows),
                'linhas' => $rows,
            ];
        }

        $snapshot = OracleCheckpointSnapshot::fromResults($queries, $results);

        return [
            'consultas' => $diagnostics,
            'snapshot' => $snapshot->toArray(),
            'sha256' => $snapshot->hash(),
        ];
    }

    /**
     * @param array<int|string, mixed> $binds
     * @return array{string, array<int|string, mixed>}
     */
    private function prepareBinds(string $name, string $sql, array $binds): array
    {
        $placeholderCount = substr_count($sql, '?');
        if ($placeholderCount !== count($binds)) {
            throw new Sprint84GateException(
                'PRECHECK_BINDS_DIVERGENTES',
                'PRECHECK_ORACLE',
                "A quantidade de binds da consulta Oracle {$name} divergiu dos placeholders.",
                [
                    'consulta' => $name,
                    'sql' => $sql,
                    'binds' => $binds,
                    'quantidade_placeholders' => $placeholderCount,
                    'quantidade_binds' => count($binds),
                ]
            );
        }
        if ($placeholderCount === 0) {
            return [$sql, []];
        }

        try {
            $this->adodb->Param(false);
            $preparedBinds = [];

            foreach (array_values($binds) as $index => $value) {
                $parameterName = 's84_p' . $index;
                $placeholder = $this->adodb->Param($parameterName);
                if (
                    !is_string($placeholder)
                    || preg_match('/^:[A-Za-z][A-Za-z0-9_]*$/D', $placeholder) !== 1
                ) {
                    throw new Sprint84GateException(
                        'PRECHECK_BIND_ORACLE_INVALIDO',
                        'PRECHECK_ORACLE',
                        "ADOdb não gerou bind nomeado válido para a consulta Oracle {$name}.",
                        [
                            'consulta' => $name,
                            'parametro' => $parameterName,
                            'tipo_placeholder' => get_debug_type($placeholder),
                            'placeholder' => $placeholder,
                        ]
                    );
                }

                $position = strpos($sql, '?');
                if ($position === false) {
                    throw new Sprint84GateException(
                        'PRECHECK_PLACEHOLDER_AUSENTE',
                        'PRECHECK_ORACLE',
                        "Um placeholder desapareceu durante a preparação da consulta Oracle {$name}.",
                        ['consulta' => $name, 'sql' => $sql, 'binds' => $binds]
                    );
                }

                $sql = substr_replace($sql, $placeholder, $position, 1);
                $preparedBinds[$parameterName] = $this->conversorCharset->converterEscrita($value);
            }
        } catch (Sprint84GateException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw new Sprint84GateException(
                'PRECHECK_PREPARACAO_BINDS_FALHOU',
                'PRECHECK_ORACLE',
                "Falha ao preparar os binds da consulta Oracle {$name}: {$failure->getMessage()}",
                ['consulta' => $name, 'sql' => $sql, 'binds' => $binds],
                $failure
            );
        }

        return [$sql, $preparedBinds];
    }

    /** @param array<int, array<string, mixed>> $rows @param array<string, int> $expected */
    private function validateCardinality(string $name, array $rows, array $expected): void
    {
        $found = count($rows);
        $valid = isset($expected['exact'])
            ? $found === $expected['exact']
            : $found >= ($expected['min'] ?? 0);

        if (!$valid) {
            throw new Sprint84GateException(
                'PRECHECK_CARDINALIDADE_DIVERGENTE',
                'PRECHECK_ORACLE',
                "A cardinalidade da consulta {$name} divergiu do checkpoint esperado.",
                ['consulta' => $name, 'esperado' => $expected, 'encontrado' => $found, 'linhas' => $rows]
            );
        }
    }

    /** @param array<int, array<string, mixed>> $rows @param string[] $expected */
    private function validateColumns(string $name, array $rows, array $expected): void
    {
        $normalizedExpected = $expected;
        sort($normalizedExpected, SORT_STRING);

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                throw new Sprint84GateException(
                    'PRECHECK_LINHA_INVALIDA',
                    'PRECHECK_ORACLE',
                    "A linha {$index} da consulta {$name} não é um array.",
                    ['consulta' => $name, 'indice' => $index, 'tipo' => get_debug_type($row), 'valor' => $row]
                );
            }

            $found = array_keys($row);
            sort($found, SORT_STRING);
            if ($found !== $normalizedExpected) {
                throw new Sprint84GateException(
                    'PRECHECK_COLUNAS_DIVERGENTES',
                    'PRECHECK_ORACLE',
                    "As colunas da consulta {$name} divergiram do contrato.",
                    ['consulta' => $name, 'indice' => $index, 'esperado' => $normalizedExpected, 'encontrado' => $found, 'linha' => $row]
                );
            }
        }
    }
}
