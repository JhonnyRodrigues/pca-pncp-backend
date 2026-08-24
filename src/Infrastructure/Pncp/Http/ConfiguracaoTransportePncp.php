<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Http;

use InvalidArgumentException;

final class ConfiguracaoTransportePncp
{
    private readonly ?string $caminhoCaBundle;

    public function __construct(
        private readonly AmbientePncp $ambiente,
        private readonly int $limitePayloadBytes,
        private readonly int $limiteRespostaBytes,
        private readonly int $timeoutConexaoSegundos = 5,
        private readonly int $timeoutLoginSegundos = 30,
        private readonly int $timeoutLeituraSegundos = 30,
        private readonly int $timeoutEscritaSegundos = 60,
        ?string $caminhoCaBundle = null
    ) {
        $this->validarPositivo($limitePayloadBytes, 'Limite de payload');
        $this->validarPositivo($limiteRespostaBytes, 'Limite de resposta');
        $this->validarPositivo($timeoutConexaoSegundos, 'Timeout de conexão');
        $this->validarPositivo($timeoutLoginSegundos, 'Timeout de login');
        $this->validarPositivo($timeoutLeituraSegundos, 'Timeout de leitura');
        $this->validarPositivo($timeoutEscritaSegundos, 'Timeout de escrita');

        if ($caminhoCaBundle !== null) {
            $caminhoCaBundle = trim($caminhoCaBundle);

            if ($caminhoCaBundle === '') {
                throw new InvalidArgumentException(
                    'Caminho do CA bundle não pode ser vazio quando informado.'
                );
            }
        }

        $this->caminhoCaBundle = $caminhoCaBundle;
    }

    public function getAmbiente(): AmbientePncp
    {
        return $this->ambiente;
    }

    public function getBaseUrl(): string
    {
        return $this->ambiente->getBaseUrl();
    }

    public function getLimitePayloadBytes(): int
    {
        return $this->limitePayloadBytes;
    }

    public function getLimiteRespostaBytes(): int
    {
        return $this->limiteRespostaBytes;
    }

    public function getTimeoutConexaoSegundos(): int
    {
        return $this->timeoutConexaoSegundos;
    }

    public function getTimeoutLoginSegundos(): int
    {
        return $this->timeoutLoginSegundos;
    }

    public function getTimeoutLeituraSegundos(): int
    {
        return $this->timeoutLeituraSegundos;
    }

    public function getTimeoutEscritaSegundos(): int
    {
        return $this->timeoutEscritaSegundos;
    }

    public function getCaminhoCaBundle(): ?string
    {
        return $this->caminhoCaBundle;
    }

    private function validarPositivo(int $valor, string $campo): void
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException("{$campo} deve ser maior que zero.");
        }
    }
}
