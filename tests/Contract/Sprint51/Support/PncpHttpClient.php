<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint51\Support;

use RuntimeException;

class PncpHttpClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $connectTimeout,
        private readonly int $readTimeout,
        private readonly int $writeTimeout,
        private readonly int $maxResponseBytes,
        private readonly ?string $caBundle = null
    ) {
        if ($baseUrl !== Sprint51Config::trainingBaseUrl()) {
            throw new RuntimeException('Cliente do gate recusou destino diferente do treinamento.');
        }
        if (!extension_loaded('curl')) {
            throw new RuntimeException('ext-curl não está carregada.');
        }
    }

    public function request(
        string $method,
        string $path,
        ?string $body = null,
        ?string $bearerToken = null
    ): PncpHttpResponse {
        $method = strtoupper($method);
        if (!in_array($method, ['GET', 'POST', 'DELETE'], true)) {
            throw new RuntimeException('Método HTTP não permitido pelo probe.');
        }
        if (!str_starts_with($path, '/') || str_contains($path, '://')) {
            throw new RuntimeException('Caminho PNCP inválido.');
        }

        $handle = curl_init($this->baseUrl . $path);
        if ($handle === false) {
            throw new RuntimeException('Não foi possível inicializar cURL.');
        }

        /** @var array<string, string[]> $responseHeaders */
        $responseHeaders = [];
        $responseBody = '';
        $requestHeaders = ['Accept: application/json'];
        if ($body !== null) {
            $requestHeaders[] = 'Content-Type: application/json';
        }
        if ($bearerToken !== null) {
            $requestHeaders[] = 'Authorization: Bearer ' . $bearerToken;
        }

        $timeout = $method === 'GET' ? $this->readTimeout : $this->writeTimeout;
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                if (str_starts_with($line, 'HTTP/')) {
                    $responseHeaders = [];
                    return strlen($line);
                }

                $separator = strpos($line, ':');
                if ($separator !== false) {
                    $name = strtolower(trim(substr($line, 0, $separator)));
                    $value = trim(substr($line, $separator + 1));
                    $responseHeaders[$name][] = $value;
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => function ($curl, string $chunk) use (&$responseBody): int {
                if (strlen($responseBody) + strlen($chunk) > $this->maxResponseBytes) {
                    return 0;
                }
                $responseBody .= $chunk;
                return strlen($chunk);
            },
        ] + $this->securityOptions();
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($handle, $options);
        $executed = curl_exec($handle);
        $curlError = curl_errno($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $sslVerifyResult = (int) curl_getinfo($handle, CURLINFO_SSL_VERIFYRESULT);
        // curl_close($handle); PHP 8+ libera automaticamente o CurlHandle ao fim do escopo.

        if ($executed === false || $curlError !== 0) {
            throw new RuntimeException("Falha cURL sanitizada (errno {$curlError}).");
        }

        return new PncpHttpResponse($status, $responseHeaders, $responseBody, $sslVerifyResult);
    }

    /** @return array<int, bool|int|string> */
    private function securityOptions(): array
    {
        $options = [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ];

        if ($this->caBundle !== null) {
            $options[CURLOPT_CAINFO] = $this->caBundle;
        }

        return $options;
    }
}
