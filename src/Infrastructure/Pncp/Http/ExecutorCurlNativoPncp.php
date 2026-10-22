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

        // PHP 8+ libera automaticamente o CurlHandle ao fim do escopo.
        try {
            if (!curl_setopt_array($handle, $opcoes)) {
                throw FalhaTransportePncpException::configuracaoCurlFalhou();
            }

            $executado = curl_exec($handle) !== false;
            $versaoCurl = curl_version();

            return new ResultadoExecucaoCurlPncp(
                $executado,
                curl_errno($handle),
                (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
                (int) curl_getinfo($handle, CURLINFO_SSL_VERIFYRESULT),
                (int) curl_getinfo($handle, CURLINFO_HTTP_VERSION),
                ($cabecalhos = curl_getinfo($handle, CURLINFO_HEADER_OUT)) !== false
                    ? (string) $cabecalhos
                    : null,
                [
                    'curlVersion' => (string) ($versaoCurl['version'] ?? ''),
                    'sslVersion' => (string) ($versaoCurl['ssl_version'] ?? ''),
                    'remoteIp' => (string) curl_getinfo($handle, CURLINFO_PRIMARY_IP),
                    'remotePort' => (int) curl_getinfo($handle, CURLINFO_PRIMARY_PORT),
                    'localIp' => (string) curl_getinfo($handle, CURLINFO_LOCAL_IP),
                    'localPort' => (int) curl_getinfo($handle, CURLINFO_LOCAL_PORT),
                ]
            );
        } catch (FalhaTransportePncpException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw FalhaTransportePncpException::configuracaoCurlFalhou();
        }
    }
}
