<?php

header('Content-Type: application/json; charset=utf-8');

putenv('PCA_SPRINT94_CONFIG_FILE=/var/www/configuracoes/pca-pncp/sprint94.php');
putenv('PCA_PNCP_PROFILE_REGISTRY_FILE=/var/www/configuracoes/pca-pncp/registro.php');

$gateRoot = $this->Ini->path_aplicacao . '/pca-pncp-sprint94-inventory-gate';
require_once $gateRoot . '/backend/vendor/autoload.php';
require_once $gateRoot . '/gate/bootstrap.php';

$loginAutenticado = trim((string) [usr_login]);
$servidor = isset($_SERVER['SERVER_NAME']) && is_string($_SERVER['SERVER_NAME'])
    ? $_SERVER['SERVER_NAME']
    : '';
$etapasPermitidas = ['PRECHECK', 'INVENTARIO'];
$etapa = strtoupper(trim((string) [etapa]));

if (!in_array($etapa, $etapasPermitidas, true)) {
    http_response_code(400);
    echo json_encode(['resultado' => 'ETAPA_INVALIDA'], JSON_THROW_ON_ERROR);
    return;
}

if ($loginAutenticado !== 'jsilva') {
    http_response_code(403);
    echo json_encode(['resultado' => 'LOGIN_NAO_AUTORIZADO'], JSON_THROW_ON_ERROR);
    return;
}

$resultado = $etapa === 'PRECHECK'
    ? executar_precheck_sprint_94($this->Db, $loginAutenticado, $servidor)
    : executar_inventario_sprint_94($this->Db, $loginAutenticado, $servidor);

echo json_encode(
    $resultado,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
