<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use Throwable;

final class Sprint84ErrorReport
{
    /** @return array<string, mixed> */
    public static function fromThrowable(string $phase, Throwable $failure): array
    {
        $error = [
            'codigo' => $failure instanceof Sprint84ConfigurationException
                || $failure instanceof Sprint84GateException
                    ? $failure->getErrorCode()
                    : 'SPRINT84_FALHA_INESPERADA',
            'classe' => $failure::class,
            'mensagem' => $failure->getMessage(),
            'arquivo' => $failure->getFile(),
            'linha' => $failure->getLine(),
            'stack' => $failure->getTraceAsString(),
        ];

        if ($failure instanceof Sprint84ConfigurationException) {
            $error += [
                'campo' => $failure->getFieldPath(),
                'esperado' => $failure->getExpected(),
                'tipo_recebido' => $failure->getActualType(),
                'valor_recebido' => $failure->getActualValue(),
            ];
            if ($failure->getProtectedActualMetadata() !== null) {
                $error['metadados_valor_protegido'] = $failure->getProtectedActualMetadata();
            }
        }

        if ($failure instanceof Sprint84GateException) {
            $error['detalhes'] = $failure->getDetails();
            $phase = $failure->getPhase();
        }

        if ($failure->getPrevious() !== null) {
            $error['causa'] = self::cause($failure->getPrevious());
        }

        return [
            'sprint' => '8.4',
            'preflight' => 'RECUSADO',
            'fase' => $phase,
            'erro' => $error,
        ];
    }

    /** @return array<string, mixed> */
    private static function cause(Throwable $failure): array
    {
        $cause = [
            'classe' => $failure::class,
            'mensagem' => $failure->getMessage(),
            'arquivo' => $failure->getFile(),
            'linha' => $failure->getLine(),
        ];

        if ($failure->getPrevious() !== null) {
            $cause['causa'] = self::cause($failure->getPrevious());
        }

        return $cause;
    }
}
