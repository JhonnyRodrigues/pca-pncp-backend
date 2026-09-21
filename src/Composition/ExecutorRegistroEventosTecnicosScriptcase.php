<?php

declare(strict_types=1);

namespace Semae\Pca\Composition;

use Semae\Pca\Infrastructure\Oracle\Adodb\ConexaoAdodbEmprestada;
use Semae\Pca\Infrastructure\Oracle\Observabilidade\OracleRegistroEventoTecnicoAdapter;
use Throwable;

final class ExecutorRegistroEventosTecnicosScriptcase
{
    private const AVISO =
        'O resultado foi preservado, mas nem todos os eventos técnicos puderam ser registrados.';

    /** @param array<int, array<string, mixed>> $eventos */
    public function registrar(
        object $conexaoAdodb,
        string $loginAutenticado,
        string $serverName,
        string $aplicacao,
        array $eventos
    ): ?string {
        if ($eventos === []) {
            return null;
        }

        try {
            $perfil = new PerfilPublicacaoPca(
                (new CarregadorPerfilPublicacaoPca())->carregar($serverName)
            );
            $adapter = new OracleRegistroEventoTecnicoAdapter(
                new ConexaoAdodbEmprestada($conexaoAdodb, $perfil->getCharsetAdodb())
            );
        } catch (Throwable) {
            return self::AVISO;
        }

        foreach ($eventos as $evento) {
            if (!is_array($evento)) {
                return self::AVISO;
            }

            try {
                $adapter->registrar($evento, $loginAutenticado, $aplicacao);
            } catch (Throwable) {
                return self::AVISO;
            }
        }

        return null;
    }
}
