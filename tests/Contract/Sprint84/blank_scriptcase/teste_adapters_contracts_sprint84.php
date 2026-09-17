<?php
header('Content-Type: application/json; charset=utf-8');

putenv('PCA_SPRINT84_CONFIG_FILE=/opt/Scriptcase/v9-php81/wwwroot/scriptcase/app/testes/teste-contract-sprint84/sprint84.php');
putenv('PCA_PNCP_PROFILE_REGISTRY_FILE=/opt/Scriptcase/v9-php81/wwwroot/scriptcase/app/testes/teste-contract-sprint84/registro.php');

$backendRoot = '/opt/Scriptcase/v9-php81/wwwroot/scriptcase/app/DEV/PCA_PUBLICAR_B/pca-pncp-backend';

require_once $backendRoot . '/vendor/autoload.php';
require_once $backendRoot . '/tests/Contract/Sprint84/scriptcase_probe.php'; // EntryPoint exclusivo para essa sprint 8.4

$loginAutenticado = (string) [usr_login];
$servidor = (string) ($_SERVER['SERVER_NAME'] ?? '');
$etapa = (string) [etapa];

$etapas = [
    'PRECHECK',
    'PUBLICAR',
    'NENHUMA_ACAO',
	'RETIFICAR',
	'EXCLUIR_PCA',
];

if (!in_array($etapa, $etapas, true)) {
    echo json_encode(
        [
            'sprint' => '8.4',
            'gate' => 'ORACLE_PNCP',
            'confirmado' => false,
            'falha' => [
                'codigo' => 'ETAPA_RECUSADA',
                'mensagem' => 'Etapa recusada, não esperada.',
                'etapa_recebida' => $etapa,
                'etapas_aceitas' => $etapas,
            ],
            'close_invocado' => false,
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
            'sprint' => '8.4',
            'gate' => 'ORACLE_PNCP',
            'confirmado' => false,
            'falha' => [
                'codigo' => 'LOGIN_SGI_NAO_AUTORIZADO',
                'mensagem' => 'A Blank foi autorizada somente para o login SGI jsilva.',
                'login_recebido' => $loginAutenticado,
                'login_esperado' => 'jsilva',
            ],
            'close_invocado' => false,
        ],
        JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_INVALID_UTF8_SUBSTITUTE
    );

    return;
}

$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
if (
    !is_string($requestId)
    || preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di',
        $requestId
    ) !== 1
) {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    $requestId = sprintf(
        '%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    );
}

try {
	$relatorio = executar_fluxos_completos_sprint_84(
		$this->Db,
		$loginAutenticado,
		$servidor,
		$requestId,
		$etapa
	);
} catch (\Throwable $failure) {
    $relatorio = \Tests\Contract\Sprint84\Support\Sprint84ErrorReport::fromThrowable(
        'BLANK_SCRIPTCASE',
        $failure
    );
    $relatorio['contexto_blank'] = [
        'login_sgi' => $loginAutenticado,
        'server_name' => $servidor,
        'request_id' => $requestId,
        'etapa' => $etapa,
    ];
}

$json = json_encode(
    $relatorio,
    JSON_PRETTY_PRINT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_PRESERVE_ZERO_FRACTION
        | JSON_INVALID_UTF8_SUBSTITUTE
);
if ($json === false) {
    echo json_encode([
        'sprint' => '8.4',
        'gate' => 'ORACLE_PNCP',
        'confirmado' => false,
        'falha' => [
            'codigo' => 'SERIALIZACAO_FALHOU',
            'mensagem' => json_last_error_msg(),
        ],
        'close_invocado' => false,
    ]);

    return;
}

echo $json;
