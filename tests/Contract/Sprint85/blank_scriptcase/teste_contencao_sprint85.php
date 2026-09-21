<?php

header('Content-Type: application/json; charset=utf-8');

putenv('PCA_SPRINT85_CONFIG_FILE=/var/www/configuracoes/pca-pncp/sprint85.php');
putenv('PCA_PNCP_PROFILE_REGISTRY_FILE=/var/www/configuracoes/pca-pncp/registro.php');

$gateRoot = $this->Ini->path_aplicacao . '/pca-pncp-containment-gate';

require_once $gateRoot . '/backend/vendor/autoload.php';
require_once $gateRoot . '/gate/bootstrap.php';

$loginAutenticado = trim((string) [usr_login]);
$servidor = isset($_SERVER['SERVER_NAME']) && is_string($_SERVER['SERVER_NAME'])
    ? $_SERVER['SERVER_NAME']
    : '';
$etapa = trim((string) [etapa]);
$etapasDisponiveis = ['PRECHECK', 'CONTENCAO', 'DISPONIBILIDADE'];

if (!in_array($etapa, $etapasDisponiveis, true)) {
    $relatorio = [
        'sprint' => '8.5',
        'resultado' => 'ETAPA_RECUSADA',
        'mensagem' => 'A etapa solicitada nao esta disponivel nesta Blank.',
        'etapas_disponiveis' => $etapasDisponiveis,
        'oracle_writes' => false,
        'pncp_writes' => false,
        'log_writes' => false,
    ];
} elseif ($loginAutenticado !== 'jsilva') {
    $relatorio = [
        'sprint' => '8.5',
        'resultado' => 'LOGIN_SGI_NAO_AUTORIZADO',
        'mensagem' => 'Esta Blank foi autorizada somente para o mantenedor do gate.',
        'oracle_writes' => false,
        'pncp_writes' => false,
        'log_writes' => false,
    ];
} elseif ($etapa === 'PRECHECK') {
    $relatorio = executar_precheck_contencao_sprint_85(
        $this->Db,
        $loginAutenticado,
        $servidor
    );
} elseif ($etapa === 'CONTENCAO') {
    $requestId = isset($_SERVER['HTTP_X_REQUEST_ID'])
        && is_string($_SERVER['HTTP_X_REQUEST_ID'])
        ? $_SERVER['HTTP_X_REQUEST_ID']
        : '';
    $relatorio = executar_contencao_sprint_85(
        $this->Db,
        $loginAutenticado,
        $servidor,
        $requestId
    );
} else {
    $relatorio = verificar_disponibilidade_contencao_sprint_85(
        $this->Db,
        $loginAutenticado,
        $servidor
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
        . '"mensagem":"Nao foi possivel serializar o relatorio seguro.",'
        . '"oracle_writes":false,"pncp_writes":false,"log_writes":false}';
    return;
}

echo $json;
