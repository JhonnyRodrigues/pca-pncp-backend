<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint51\Support;

use RuntimeException;
use Throwable;

/**
 * Probe diagnóstico para conexão ADOdb emprestada pelo Scriptcase.
 *
 * Não implementa Ports e, deliberadamente, não possui operação de fechamento
 * da conexão. O ciclo de vida continua pertencendo ao host.
 */
final class OracleScriptcaseProbe
{
    private bool $fixtureRestorationFailed = false;

    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly object $db,
        private readonly array $config
    ) {
        Sprint51Config::oracle(['oracle' => $config]);

        $allowlist = array_map('strtoupper', $config['tables']);
        foreach (['fixture', 'lock'] as $section) {
            if (!in_array(strtoupper((string) $config[$section]['table']), $allowlist, true)) {
                throw new RuntimeException("Tabela de {$section} não pertence à allowlist.");
            }
        }
    }

    /** @param array<string, mixed> $config */
    public static function fromConfig(object $db, array $config): self
    {
        return new self($db, Sprint51Config::oracle($config));
    }

    /** @return array<string, mixed> */
    public function run(string $mode = 'discovery'): array
    {
        $base = [
            'sprint' => '5.1',
            'gate' => 'ORACLE_SCRIPTCASE',
            'modo' => $mode,
            'executado_em_utc' => gmdate('c'),
            'conexao_emprestada' => true,
            'conexao_fechada_pelo_probe' => false,
        ];

        try {
            $result = match ($mode) {
                'discovery' => $this->runDiscovery(),
                'lock_holder' => $this->holdLock(),
                'lock_expect_blocked' => $this->attemptLock(true),
                'lock_expect_available' => $this->attemptLock(false),
                default => throw new RuntimeException('Modo Oracle desconhecido.'),
            };

            $confirmed = ($result['confirmado'] ?? true) === true
                && !$this->fixtureRestorationFailed;
            return $base + [
                'confirmado' => $confirmed,
                'residuo_fixture_possivel' => $this->fixtureRestorationFailed,
                'resultado' => $result,
            ];
        } catch (Throwable $exception) {
            $this->safeRollback();
            return $base + [
                'confirmado' => false,
                'falha' => [
                    'tipo' => $exception::class,
                    'codigo_oracle' => $this->oracleErrorCode(),
                    'mensagem' => $this->safeMessage($exception),
                ],
                'residuo_fixture_possivel' => $this->fixtureRestorationFailed,
            ];
        }
    }

    /** @return array<string, mixed> */
    private function runDiscovery(): array
    {
        $this->requireMethods([
            'BeginTrans',
            'CommitTrans',
            'RollbackTrans',
            'Execute',
            'GetOne',
            'GetAll',
            'Param',
            'ErrorNo',
            'ErrorMsg',
        ]);

        $sessionBefore = $this->sessionFingerprint();
        $bindResult = $this->getOne(
            'SELECT ? FROM DUAL',
            ['SPRINT51_BIND'],
            'bind_controlado'
        );
        $dateResult = $this->getOne(
            "SELECT TO_CHAR(DATE '2026-08-17', 'YYYY-MM-DD') FROM DUAL",
            [],
            'data_controlada'
        );
        $decimalResult = $this->getOne(
            "SELECT TO_CHAR(CAST(? AS NUMBER(17,4)), 'FM9999999999990D0000', "
            . "'NLS_NUMERIC_CHARACTERS=''.,''') FROM DUAL",
            ['1.2300'],
            'decimal_controlado'
        );

        $invalidSucceeded = $this->execute('SELECT 1 / ? FROM DUAL', [0]) !== false;
        $sanitizedOracleError = $this->oracleErrorCode();
        if ($invalidSucceeded) {
            throw new RuntimeException('Consulta inválida controlada foi aceita inesperadamente.');
        }

        $sessionAfter = $this->sessionFingerprint();
        if ($sessionBefore !== $sessionAfter) {
            throw new RuntimeException('A sessão Oracle mudou durante o probe básico.');
        }

        $runtime = $this->runtimeEvidence();
        $schema = $this->schemaEvidence();
        $aggregates = $this->aggregateEvidence();
        $transaction = $this->transactionEvidence();
        $contractConfirmed = $bindResult === 'SPRINT51_BIND'
            && $sanitizedOracleError !== null
            && $dateResult === '2026-08-17'
            && $decimalResult === '1.2300'
            && $runtime['adodb_versao'] !== null
            && $schema['confirmado'] === true
            && $aggregates['confirmado'] === true;

        return [
            'confirmado' => $contractConfirmed,
            'runtime' => $runtime,
            'contrato_adodb' => [
                'metodos_obrigatorios' => true,
                'bind_posicional' => $bindResult === 'SPRINT51_BIND',
                'erro_controlado_traduzivel' => $sanitizedOracleError !== null,
                'codigo_erro_sanitizado' => $sanitizedOracleError,
                'mesma_sessao' => true,
            ],
            'datas_decimais' => [
                'data_calendario_sem_timezone' => $dateResult === '2026-08-17',
                'number_17_4_exato' => $decimalResult === '1.2300',
                'nls' => $this->nlsEvidence(),
            ],
            'schema' => $schema,
            'agregacoes' => $aggregates,
            'transacao' => $transaction,
        ];
    }

    /** @return array<string, mixed> */
    private function runtimeEvidence(): array
    {
        global $ADODB_vers;

        $oracleVersion = null;
        try {
            $value = $this->getOne(
                "SELECT VERSION FROM PRODUCT_COMPONENT_VERSION WHERE PRODUCT LIKE 'Oracle%' "
                . 'AND ROWNUM = 1',
                [],
                'versao_oracle'
            );
            $oracleVersion = is_scalar($value) ? (string) $value : null;
        } catch (Throwable) {
            // A versão é evidência auxiliar; a ausência de privilégio fica explícita.
        }

        return [
            'php' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'oci8_extensao' => phpversion('oci8') ?: null,
            'adodb_classe' => $this->db::class,
            'adodb_versao' => isset($ADODB_vers) && is_scalar($ADODB_vers) ? (string) $ADODB_vers : null,
            'oracle_versao' => $oracleVersion,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function nlsEvidence(): array
    {
        $rows = $this->getAll(
            "SELECT PARAMETER, VALUE FROM NLS_SESSION_PARAMETERS WHERE PARAMETER IN "
            . "('NLS_DATE_FORMAT', 'NLS_TIMESTAMP_FORMAT', 'NLS_TIMESTAMP_TZ_FORMAT', "
            . "'NLS_NUMERIC_CHARACTERS') ORDER BY PARAMETER",
            [],
            'nls_sessao'
        );

        return array_map(static function (array $row): array {
            $row = array_change_key_case($row, CASE_UPPER);
            return [
                'parametro' => $row['PARAMETER'] ?? null,
                'valor' => $row['VALUE'] ?? null,
            ];
        }, $rows);
    }

    /** @return array<string, mixed> */
    private function schemaEvidence(): array
    {
        $owner = strtoupper((string) $this->config['schema_owner']);
        $tables = array_values(array_unique(array_map('strtoupper', $this->config['tables'])));
        $placeholders = implode(',', array_fill(0, count($tables), '?'));
        $params = [$owner, ...$tables];

        $columns = $this->getAll(
            "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION, DATA_SCALE, "
            . "NULLABLE, CASE WHEN DATA_DEFAULT IS NULL THEN 'N' ELSE 'S' END AS HAS_DEFAULT "
            . "FROM ALL_TAB_COLUMNS WHERE OWNER = ? AND TABLE_NAME IN ({$placeholders}) "
            . 'ORDER BY TABLE_NAME, COLUMN_ID',
            $params,
            'schema_colunas'
        );
        $constraints = $this->getAll(
            "SELECT C.TABLE_NAME, C.CONSTRAINT_TYPE, CC.COLUMN_NAME, CC.POSITION, "
            . "RC.TABLE_NAME AS REFERENCED_TABLE "
            . 'FROM ALL_CONSTRAINTS C '
            . 'LEFT JOIN ALL_CONS_COLUMNS CC ON CC.OWNER = C.OWNER AND CC.CONSTRAINT_NAME = C.CONSTRAINT_NAME '
            . 'LEFT JOIN ALL_CONSTRAINTS RC ON RC.OWNER = C.R_OWNER AND RC.CONSTRAINT_NAME = C.R_CONSTRAINT_NAME '
            . "WHERE C.OWNER = ? AND C.TABLE_NAME IN ({$placeholders}) "
            . "AND C.CONSTRAINT_TYPE IN ('P','U','R','C') "
            . 'ORDER BY C.TABLE_NAME, C.CONSTRAINT_TYPE, CC.POSITION',
            $params,
            'schema_constraints'
        );
        $indexes = $this->getAll(
            'SELECT I.TABLE_NAME, I.UNIQUENESS, IC.COLUMN_NAME, IC.COLUMN_POSITION '
            . 'FROM ALL_INDEXES I JOIN ALL_IND_COLUMNS IC '
            . 'ON IC.INDEX_OWNER = I.OWNER AND IC.INDEX_NAME = I.INDEX_NAME '
            . "WHERE I.OWNER = ? AND I.TABLE_NAME IN ({$placeholders}) "
            . 'ORDER BY I.TABLE_NAME, I.UNIQUENESS, IC.COLUMN_POSITION',
            $params,
            'schema_indices'
        );

        $observedTables = [];
        foreach ($columns as $column) {
            $normalized = array_change_key_case($column, CASE_UPPER);
            if (is_string($normalized['TABLE_NAME'] ?? null)) {
                $observedTables[] = $normalized['TABLE_NAME'];
            }
        }
        $observedTables = array_values(array_unique($observedTables));
        sort($observedTables);
        $expectedTables = $tables;
        sort($expectedTables);

        return [
            'confirmado' => $observedTables === $expectedTables,
            'owner_registrado' => false,
            'tabelas_allowlisted' => $tables,
            'colunas' => array_map($this->sanitizeColumn(...), $columns),
            'constraints' => array_map($this->sanitizeConstraint(...), $constraints),
            'indices' => array_map($this->sanitizeIndex(...), $indexes),
        ];
    }

    /** @return array<string, mixed> */
    private function aggregateEvidence(): array
    {
        $evidence = [];
        foreach ($this->config['aggregate_checks'] as $index => $check) {
            $actual = $this->getOne(
                $check['sql'],
                $check['params'],
                'agregacao_' . ((int) $index + 1)
            );
            $operator = $check['expect']['operator'];
            $expected = $check['expect']['value'] ?? null;
            $confirmed = match ($operator) {
                'eq' => $actual == $expected,
                'neq' => $actual != $expected,
                'lt' => $actual < $expected,
                'lte' => $actual <= $expected,
                'gt' => $actual > $expected,
                'gte' => $actual >= $expected,
                'not_null' => $actual !== null,
                default => false,
            };
            $evidence[] = [
                'rotulo' => $check['label'],
                'operador' => $operator,
                'confirmado' => $confirmed,
                'valor_observado_registrado' => false,
            ];
        }

        return [
            'confirmado' => $evidence !== []
                && count(array_filter($evidence, static fn (array $item): bool => $item['confirmado'] === true))
                    === count($evidence),
            'checks' => $evidence,
        ];
    }

    /** @return array<string, bool> */
    private function transactionEvidence(): array
    {
        $fixture = $this->config['fixture'];
        $original = $this->fixtureValue();
        $probeValue = (string) $fixture['probe_value'];
        try {
            $session = $this->sessionFingerprint();
            $this->begin();
            $this->updateFixture($probeValue);
            $visibleBeforeRollback = (string) $this->fixtureValue() === $probeValue;
            $this->rollback();
            $rollbackRestored = $this->sameScalar($this->fixtureValue(), $original);

            $this->begin();
            $this->updateFixture($probeValue);
            $this->commit();
            $commitVisible = (string) $this->fixtureValue() === $probeValue;

            $this->begin();
            $this->updateFixture($original);
            $this->commit();
            $restored = $this->sameScalar($this->fixtureValue(), $original);
            $sameSession = $session === $this->sessionFingerprint();

            if (!$visibleBeforeRollback || !$rollbackRestored || !$commitVisible || !$restored || !$sameSession) {
                throw new RuntimeException('Prova transacional não satisfez todas as verificações.');
            }

            return [
                'alteracao_visivel_antes_rollback' => true,
                'rollback_real' => true,
                'commit_real' => true,
                'estado_original_restaurado' => true,
                'mesma_sessao' => true,
            ];
        } finally {
            $this->safeRollback();
            try {
                if (!$this->sameScalar($this->fixtureValue(), $original)) {
                    $this->begin();
                    $this->updateFixture($original);
                    $this->commit();
                }
            } catch (Throwable) {
                $this->fixtureRestorationFailed = true;
                $this->safeRollback();
            }
        }
    }

    /** @return array<string, mixed> */
    private function holdLock(): array
    {
        $lock = $this->config['lock'];
        $session = $this->sessionFingerprint();
        $this->begin();
        try {
            $result = $this->execute($this->lockSql(), [$lock['key_value']]);
            if ($result === false) {
                throw new RuntimeException('Sessão detentora não adquiriu o lock NOWAIT.');
            }
            sleep((int) $lock['hold_seconds']);
            $sameSession = $session === $this->sessionFingerprint();
            if (!$sameSession) {
                throw new RuntimeException('Sessão Oracle mudou durante a retenção do lock.');
            }
            return [
                'lock_adquirido' => true,
                'segundos_retencao' => (int) $lock['hold_seconds'],
                'mesma_sessao' => true,
                'liberacao' => 'ROLLBACK_NO_FINALLY',
            ];
        } finally {
            $this->rollback();
        }
    }

    /** @return array<string, mixed> */
    private function attemptLock(bool $expectBlocked): array
    {
        $lock = $this->config['lock'];
        $this->begin();
        try {
            $result = $this->execute($this->lockSql(), [$lock['key_value']]);
            $error = $result === false ? $this->oracleErrorCode() : null;
            $blocked = $result === false && $error !== null && str_contains($error, '00054');

            if ($expectBlocked && !$blocked) {
                throw new RuntimeException('Contenção NOWAIT esperada não produziu ORA-00054.');
            }
            if (!$expectBlocked && $result === false) {
                throw new RuntimeException('Lock deveria estar disponível após a liberação.');
            }

            return [
                'expectativa' => $expectBlocked ? 'BLOQUEADO' : 'DISPONIVEL',
                'bloqueado_imediatamente' => $blocked,
                'lock_adquirido' => $result !== false,
                'codigo_oracle' => $error,
            ];
        } finally {
            $this->rollback();
        }
    }

    private function lockSql(): string
    {
        $lock = $this->config['lock'];
        return 'SELECT ' . strtoupper((string) $lock['key_column'])
            . ' FROM ' . $this->qualified((string) $lock['table'])
            . ' WHERE ' . strtoupper((string) $lock['key_column']) . ' = ? FOR UPDATE NOWAIT';
    }

    private function fixtureValue(): mixed
    {
        $fixture = $this->config['fixture'];
        return $this->getOne(
            'SELECT ' . strtoupper((string) $fixture['value_column'])
            . ' FROM ' . $this->qualified((string) $fixture['table'])
            . ' WHERE ' . strtoupper((string) $fixture['key_column']) . ' = ?',
            [$fixture['key_value']],
            'leitura_fixture'
        );
    }

    private function updateFixture(mixed $value): void
    {
        $fixture = $this->config['fixture'];
        $result = $this->execute(
            'UPDATE ' . $this->qualified((string) $fixture['table'])
            . ' SET ' . strtoupper((string) $fixture['value_column']) . ' = ?'
            . ' WHERE ' . strtoupper((string) $fixture['key_column']) . ' = ?',
            [$value, $fixture['key_value']]
        );
        if ($result === false) {
            throw new RuntimeException('Alteração controlada da fixture Oracle falhou.');
        }
    }

    private function sessionFingerprint(): string
    {
        $value = $this->getOne(
            "SELECT SYS_CONTEXT('USERENV','INSTANCE') || ':' || SYS_CONTEXT('USERENV','SID') FROM DUAL",
            [],
            'fingerprint_sessao'
        );
        if (!is_scalar($value) || (string) $value === '') {
            throw new RuntimeException('Não foi possível obter fingerprint efêmero da sessão Oracle.');
        }

        return (string) $value;
    }

    private function qualified(string $table): string
    {
        return strtoupper((string) $this->config['schema_owner']) . '.' . strtoupper($table);
    }

    /** @param string[] $methods */
    private function requireMethods(array $methods): void
    {
        foreach ($methods as $method) {
            if (!method_exists($this->db, $method)) {
                throw new RuntimeException("ADOdb não oferece o método obrigatório {$method}.");
            }
        }
    }

    private function begin(): void
    {
        if ($this->db->BeginTrans() === false) {
            throw new RuntimeException('ADOdb recusou início explícito da transação.');
        }
    }

    private function commit(): void
    {
        if ($this->db->CommitTrans() === false) {
            throw new RuntimeException('ADOdb recusou commit explícito.');
        }
    }

    private function rollback(): void
    {
        if ($this->db->RollbackTrans() === false) {
            throw new RuntimeException('ADOdb recusou rollback explícito.');
        }
    }

    private function safeRollback(): void
    {
        try {
            if (method_exists($this->db, 'RollbackTrans')) {
                $this->db->RollbackTrans();
            }
        } catch (Throwable) {
            // O relatório preserva a falha original; não expõe mensagem do driver.
        }
    }

    /** @param array<int|string, mixed> $params */
    private function execute(string $sql, array $params = []): mixed
    {
        [$sql, $params] = $this->oracleBinds($sql, $params);

        return $this->db->Execute($sql, $params);
    }

    /** @param array<int|string, mixed> $params */
    private function getOne(
        string $sql,
        array $params = [],
        string $stage = 'consulta_escalar'
    ): mixed
    {
        [$sql, $params] = $this->oracleBinds($sql, $params);
        $value = $this->db->GetOne($sql, $params);
        if ($value === false) {
            throw new RuntimeException("ADOdb recusou consulta escalar na etapa {$stage}.");
        }

        return $value;
    }

    /** @param array<int|string, mixed> $params @return array<int, array<string, mixed>> */
    private function getAll(
        string $sql,
        array $params = [],
        string $stage = 'consulta_colecao'
    ): array
    {
        [$sql, $params] = $this->oracleBinds($sql, $params);
        $rows = $this->db->GetAll($sql, $params);
        if (!is_array($rows)) {
            throw new RuntimeException("ADOdb não retornou coleção na etapa {$stage}.");
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * Converte os placeholders portáveis do probe para binds nomeados do
     * driver Oracle. Enviar `?` diretamente ao OCI8 resulta em ORA-00911 em
     * runtimes ADOdb que não fazem essa conversão internamente.
     *
     * @param array<int|string, mixed> $params
     * @return array{string, array<int|string, mixed>}
     */
    private function oracleBinds(string $sql, array $params): array
    {
        $placeholderCount = substr_count($sql, '?');
        if ($placeholderCount === 0) {
            return [$sql, $params];
        }
        if ($placeholderCount !== count($params)) {
            throw new RuntimeException('Quantidade de binds diverge dos placeholders do probe Oracle.');
        }
        if (!method_exists($this->db, 'Param')) {
            throw new RuntimeException('ADOdb não oferece o método obrigatório Param.');
        }

        $this->db->Param(false);
        $bound = [];
        foreach (array_values($params) as $index => $value) {
            $name = 's51_p' . $index;
            $placeholder = $this->db->Param($name);
            if (!is_string($placeholder)
                || !preg_match('/^:[A-Za-z][A-Za-z0-9_]*$/D', $placeholder)
            ) {
                throw new RuntimeException('ADOdb não gerou bind nomeado compatível com Oracle.');
            }

            $position = strpos($sql, '?');
            if ($position === false) {
                throw new RuntimeException('Placeholder Oracle desapareceu durante a preparação do probe.');
            }
            $sql = substr_replace($sql, $placeholder, $position, 1);
            $bound[$name] = $value;
        }

        return [$sql, $bound];
    }

    private function oracleErrorCode(): ?string
    {
        try {
            $number = $this->db->ErrorNo();
            if (is_int($number) || (is_string($number) && ctype_digit(ltrim($number, '-')))) {
                $absolute = abs((int) $number);
                if ($absolute > 0) {
                    return 'ORA-' . str_pad((string) $absolute, 5, '0', STR_PAD_LEFT);
                }
            }

            $message = $this->db->ErrorMsg();
            if (is_string($message) && preg_match('/ORA-[0-9]{5}/', $message, $matches)) {
                return $matches[0];
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function safeMessage(Throwable $exception): string
    {
        if ($exception instanceof RuntimeException && $exception->getFile() === __FILE__) {
            return $exception->getMessage();
        }

        return 'Falha externa sanitizada; consulte apenas o tipo e codigo_oracle.';
    }

    private function sameScalar(mixed $left, mixed $right): bool
    {
        if ($left === null && $right === null) {
            return true;
        }
        return is_scalar($left) && is_scalar($right) && (string) $left === (string) $right;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function sanitizeColumn(array $row): array
    {
        $row = array_change_key_case($row, CASE_UPPER);
        return [
            'tabela' => $row['TABLE_NAME'] ?? null,
            'coluna' => $row['COLUMN_NAME'] ?? null,
            'tipo' => $row['DATA_TYPE'] ?? null,
            'tamanho' => $row['DATA_LENGTH'] ?? null,
            'precisao' => $row['DATA_PRECISION'] ?? null,
            'escala' => $row['DATA_SCALE'] ?? null,
            'nulavel' => ($row['NULLABLE'] ?? null) === 'Y',
            'possui_default' => ($row['HAS_DEFAULT'] ?? null) === 'S',
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function sanitizeConstraint(array $row): array
    {
        $row = array_change_key_case($row, CASE_UPPER);
        return [
            'tabela' => $row['TABLE_NAME'] ?? null,
            'tipo' => $row['CONSTRAINT_TYPE'] ?? null,
            'coluna' => $row['COLUMN_NAME'] ?? null,
            'posicao' => $row['POSITION'] ?? null,
            'tabela_referenciada' => $row['REFERENCED_TABLE'] ?? null,
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function sanitizeIndex(array $row): array
    {
        $row = array_change_key_case($row, CASE_UPPER);
        return [
            'tabela' => $row['TABLE_NAME'] ?? null,
            'unico' => ($row['UNIQUENESS'] ?? null) === 'UNIQUE',
            'coluna' => $row['COLUMN_NAME'] ?? null,
            'posicao' => $row['COLUMN_POSITION'] ?? null,
        ];
    }
}
