<?php

declare(strict_types=1);

use Tests\Contract\Sprint51\Support\OracleScriptcaseProbe;
use Tests\Contract\Sprint51\Support\Sprint51Config;

/**
 * Ponto de chamada para uma aplicação Blank protegida no Scriptcase.
 *
 * O host deve carregar vendor/autoload.php e chamar esta função passando
 * $this->Db. O retorno já está sanitizado e pode ser serializado como JSON.
 *
 * @return array<string, mixed>
 */
function executar_probe_oracle_sprint_51(object $db, string $mode = 'discovery'): array
{
    try {
        $config = Sprint51Config::loadFromEnvironment();
        return OracleScriptcaseProbe::fromConfig($db, $config)->run($mode);
    } catch (Throwable $exception) {
        return [
            'sprint' => '5.1',
            'gate' => 'ORACLE_SCRIPTCASE',
            'confirmado' => false,
            'falha' => [
                'tipo' => $exception::class,
                'mensagem' => 'Configuração ou inicialização recusada sem exposição de detalhes.',
            ],
            'conexao_fechada_pelo_probe' => false,
        ];
    }
}
