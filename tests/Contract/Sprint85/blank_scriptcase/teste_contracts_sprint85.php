<?php

header('Content-Type: application/json; charset=utf-8');

function sprint85_log_gate_uuid_v4_valido(string $value): bool
{
    return preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di',
        $value
    ) === 1;
}

/** @return array<string, mixed> */
function sprint85_log_gate_diagnosticar_correlacao(): array
{
    $requestId = isset($_SERVER['HTTP_X_REQUEST_ID'])
        && is_string($_SERVER['HTTP_X_REQUEST_ID'])
        ? $_SERVER['HTTP_X_REQUEST_ID']
        : null;
    $correlationId = isset($_SERVER['HTTP_X_CORRELATION_ID'])
        && is_string($_SERVER['HTTP_X_CORRELATION_ID'])
        ? $_SERVER['HTTP_X_CORRELATION_ID']
        : null;

    return [
        'http_x_request_id' => [
            'presente' => $requestId !== null,
            'valor' => $requestId,
            'tamanho' => $requestId === null ? null : strlen($requestId),
            'uuid_v4_valido' => $requestId !== null
                && sprint85_log_gate_uuid_v4_valido($requestId),
        ],
        'http_x_correlation_id' => [
            'presente' => $correlationId !== null,
            'valor' => $correlationId,
            'tamanho' => $correlationId === null ? null : strlen($correlationId),
            'uuid_v4_valido' => $correlationId !== null
                && sprint85_log_gate_uuid_v4_valido($correlationId),
        ],
    ];
}

/** @return array<string, bool> */
function sprint85_log_gate_diagnosticar_clob_adodb(object $db): array
{
    return [
        'update_clob_disponivel' => method_exists($db, 'UpdateClob'),
        'begin_trans_disponivel' => method_exists($db, 'BeginTrans'),
        'commit_trans_disponivel' => method_exists($db, 'CommitTrans'),
        'rollback_trans_disponivel' => method_exists($db, 'RollbackTrans'),
    ];
}

putenv('PCA_SPRINT85_LOG_GATE_CONFIG_FILE=/var/www/html/teste_contracts_sprint85/sprint85-logs-homologacao.php');

$gateRoot = $this->Ini->path_aplicacao . '/pca-pncp-log-gate';

require_once $gateRoot . '/backend/vendor/autoload.php';
require_once $gateRoot . '/gate/bootstrap.php';

$loginAutenticado = trim((string) [usr_login]);
$servidor = isset($_SERVER['SERVER_NAME']) && is_string($_SERVER['SERVER_NAME'])
    ? $_SERVER['SERVER_NAME']
    : '';
$etapa = trim((string) [etapa]);
$etapasDisponiveis = ['PRECHECK', 'LOGS'];

if (!in_array($etapa, $etapasDisponiveis, true)) {
    echo json_encode(
        [
            'sprint' => '8.5',
            'resultado' => 'ETAPA_RECUSADA',
            'mensagem' => 'A etapa solicitada não está disponível nesta Blank.',
            'etapasDisponiveis' => $etapasDisponiveis,
            'oracle_writes' => false,
            'pncp_access' => false,
            'pncp_writes' => false,
            'log_writes' => false,
        ],
        JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_INVALID_UTF8_SUBSTITUTE
    );

    return;
}

if ($loginAutenticado !== 'jsilva') {
    echo json_encode(
        [
            'sprint' => '8.5',
            'resultado' => 'LOGIN_SGI_NAO_AUTORIZADO',
            'mensagem' => 'Esta Blank foi autorizada somente para o mantenedor do gate.',
            'oracle_writes' => false,
            'pncp_access' => false,
            'pncp_writes' => false,
            'log_writes' => false,
        ],
        JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_INVALID_UTF8_SUBSTITUTE
    );

    return;
}

try {
    if ($etapa === 'PRECHECK') {
        $relatorio = executar_precheck_logs_sprint_85($this->Db, $servidor);
        $relatorio['diagnostico_correlacao_http'] = sprint85_log_gate_diagnosticar_correlacao();
        $relatorio['diagnostico_clob_adodb'] = sprint85_log_gate_diagnosticar_clob_adodb($this->Db);
    } else {
        $requestId = isset($_SERVER['HTTP_X_REQUEST_ID'])
            && is_string($_SERVER['HTTP_X_REQUEST_ID'])
            ? $_SERVER['HTTP_X_REQUEST_ID']
            : '';
        $preparacao = preparar_log_sintetico_sprint_85(
            $this->Db,
            $servidor,
            $requestId
        );

        // Única escrita do gate: o registrador Oracle de CLOB do pacote.
        persistir_log_sintetico_sprint_85(
            $this->Db,
            $servidor,
            $preparacao['json'],
            $loginAutenticado,
            'teste_contracts_sprint85'
        );

        $relatorio = verificar_log_sintetico_sprint_85(
            $this->Db,
            $servidor,
            $preparacao['json']
        );
        $relatorio['bytes_enviados'] = $preparacao['bytes'];
        $relatorio['sha256_enviado'] = $preparacao['sha256'];
        $relatorio['correlacao'] = $requestId;
    }
} catch (\Throwable $failure) {
    $relatorio = sprint85_relatorio_falha_logs(
        $failure,
        $etapa === 'LOGS' ? 'LOGS_NAO_CONFIRMADOS' : 'PRECHECK_LOGS_RECUSADO',
        $etapa === 'LOGS' && isset($preparacao)
    );
}

$json = json_encode(
    $relatorio,
    JSON_PRETTY_PRINT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_PRESERVE_ZERO_FRACTION
        | JSON_INVALID_UTF8_SUBSTITUTE
);

if (!is_string($json)) {
    echo '{"sprint":"8.5","resultado":"SERIALIZACAO_FALHOU",'
        . '"mensagem":"Não foi possível serializar o relatório seguro.",'
        . '"oracle_writes":false,"pncp_access":false,'
        . '"pncp_writes":false,"log_writes":false}';

    return;
}

echo $json;
