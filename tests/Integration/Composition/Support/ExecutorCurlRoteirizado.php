<?php

declare(strict_types=1);

namespace Tests\Integration\Composition\Support;

use RuntimeException;
use Semae\Pca\Infrastructure\Pncp\Http\ExecutorCurlPncp;
use Semae\Pca\Infrastructure\Pncp\Http\ResultadoExecucaoCurlPncp;

final class ExecutorCurlRoteirizado implements ExecutorCurlPncp
{
    /** @var array<int, array{metodo: string, url: string, rota: string, status: int, headers: string[], corpo: string}> */
    private array $fila = [];

    /** @var array<int, array{metodo: string, rota: string, headers: string[], corpo: ?string}> */
    private array $chamadas = [];

    public function __construct(private readonly RegistroCronologico $eventos) {}

    /** @param string[] $headers */
    public function programar(
        string $metodo,
        string $url,
        string $rota,
        int $status,
        array $headers,
        string $corpo
    ): void {
        $this->fila[] = compact('metodo', 'url', 'rota', 'status', 'headers', 'corpo');
    }

    /** @param array<int, mixed> $opcoes */
    public function executar(string $url, array $opcoes): ResultadoExecucaoCurlPncp
    {
        $programada = array_shift($this->fila);
        $metodo = $opcoes[CURLOPT_CUSTOMREQUEST] ?? null;

        if (
            $programada === null
            || $programada['metodo'] !== $metodo
            || $programada['url'] !== $url
        ) {
            throw new RuntimeException('Chamada cURL inesperada no cenÃ¡rio controlado.');
        }

        $this->chamadas[] = [
            'metodo' => $programada['metodo'],
            'rota' => $programada['rota'],
            'headers' => $opcoes[CURLOPT_HTTPHEADER] ?? [],
            'corpo' => $opcoes[CURLOPT_POSTFIELDS] ?? null,
        ];
        $this->eventos->registrar(
            'pncp:' . $programada['metodo'] . ':' . $programada['rota']
        );

        $cabecalho = $opcoes[CURLOPT_HEADERFUNCTION] ?? null;
        $escrita = $opcoes[CURLOPT_WRITEFUNCTION] ?? null;

        if (!is_callable($cabecalho) || !is_callable($escrita)) {
            throw new RuntimeException('Callbacks cURL obrigatÃ³rios ausentes.');
        }

        $cabecalho(null, "HTTP/1.1 {$programada['status']} Controlled\r\n");

        foreach ($programada['headers'] as $header) {
            $cabecalho(null, $header . "\r\n");
        }

        $cabecalho(null, "\r\n");

        if ($programada['corpo'] !== '') {
            $escrita(null, $programada['corpo']);
        }

        return new ResultadoExecucaoCurlPncp(true, 0, $programada['status'], 0);
    }

    public function quantidadeChamadas(string $metodo, string $rota): int
    {
        return count(array_filter(
            $this->chamadas,
            static fn (array $chamada): bool => $chamada['metodo'] === $metodo
                && $chamada['rota'] === $rota
        ));
    }

    public function quantidadeEscritasPncp(): int
    {
        return count(array_filter(
            $this->chamadas,
            static fn (array $chamada): bool => in_array(
                $chamada['metodo'],
                ['PUT', 'PATCH', 'DELETE'],
                true
            ) || ($chamada['metodo'] === 'POST' && $chamada['rota'] !== 'login')
        ));
    }

    /** @return string[] */
    public function rotasEscritas(): array
    {
        return array_values(array_map(
            static fn (array $chamada): string => $chamada['rota'],
            array_filter(
                $this->chamadas,
                static fn (array $chamada): bool => in_array(
                    $chamada['metodo'],
                    ['PUT', 'PATCH', 'DELETE'],
                    true
                ) || ($chamada['metodo'] === 'POST' && $chamada['rota'] !== 'login')
            )
        ));
    }

    /** @return string[] */
    public function bearersAposLogin(): array
    {
        $bearers = [];

        foreach ($this->chamadas as $chamada) {
            if ($chamada['rota'] === 'login') {
                continue;
            }

            foreach ($chamada['headers'] as $header) {
                if (stripos($header, 'Authorization:') === 0) {
                    $bearers[] = trim(substr($header, strlen('Authorization:')));
                    continue 2;
                }
            }

            $bearers[] = '';
        }

        return $bearers;
    }

    /** @return int[] */
    public function idsDoPayload(string $rota): array
    {
        foreach ($this->chamadas as $chamada) {
            if ($chamada['rota'] !== $rota || $chamada['corpo'] === null) {
                continue;
            }

            $payload = json_decode($chamada['corpo'], true, 64, JSON_THROW_ON_ERROR);

            if ($rota === 'excluir_itens') {
                return $payload['listaNumerosItens'] ?? [];
            }

            return array_column($payload, 'numeroItem');
        }

        return [];
    }

    /** @return string[] */
    public function respostasPendentes(): array
    {
        return array_map(
            static fn (array $resposta): string => $resposta['metodo'] . ':' . $resposta['rota'],
            $this->fila
        );
    }
}
