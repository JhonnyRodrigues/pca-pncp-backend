<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

use Throwable;

final class ExecutorCurlNativoPncp implements ExecutorCurlPncp
{
    /** @param array<int, mixed> $opcoes */
    public function executar(string $url, array $opcoes): ResultadoExecucaoCurlPncp
    {
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            throw FalhaTransportePncpException::extensaoCurlIndisponivel();
        }

        $handle = curl_init($url);

        if ($handle === false) {
            throw FalhaTransportePncpException::inicializacaoCurlFalhou();
        }

        try {
            if (!curl_setopt_array($handle, $opcoes)) {
                throw FalhaTransportePncpException::configuracaoCurlFalhou();
            }

            $executado = curl_exec($handle) !== false;

            return new ResultadoExecucaoCurlPncp(
                $executado,
                curl_errno($handle),
                (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
                (int) curl_getinfo($handle, CURLINFO_SSL_VERIFYRESULT)
            );
        } catch (FalhaTransportePncpException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw FalhaTransportePncpException::configuracaoCurlFalhou();
        } finally {
            curl_close($handle);
        }
    }
}
