<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Pncp\Leitura;

use InvalidArgumentException;

final class ConfiguracaoLeituraPncp
{
    public function __construct(
        private readonly int $tamanhoPagina,
        private readonly int $profundidadeMaximaJson,
        private readonly int $limiteMaximoPaginas,
        private readonly int $esperaRetryPadraoMs,
        private readonly int $tetoRetryAfterMs
    ) {
        if ($tamanhoPagina <= 0) {
            throw new InvalidArgumentException('Tamanho da página PNCP deve ser maior que zero.');
        }

        if ($profundidadeMaximaJson <= 0) {
            throw new InvalidArgumentException('Profundidade máxima do JSON PNCP deve ser maior que zero.');
        }

        if ($limiteMaximoPaginas <= 0) {
            throw new InvalidArgumentException('Limite de páginas PNCP deve ser maior que zero.');
        }

        if ($esperaRetryPadraoMs <= 0) {
            throw new InvalidArgumentException('Espera padrão de retry PNCP deve ser maior que zero.');
        }

        if ($tetoRetryAfterMs <= 0 || $esperaRetryPadraoMs > $tetoRetryAfterMs) {
            throw new InvalidArgumentException('Teto de Retry-After PNCP é inválido.');
        }
    }

    public function getTamanhoPagina(): int
    {
        return $this->tamanhoPagina;
    }

    public function getProfundidadeMaximaJson(): int
    {
        return $this->profundidadeMaximaJson;
    }

    public function getLimiteMaximoPaginas(): int
    {
        return $this->limiteMaximoPaginas;
    }

    public function getEsperaRetryPadraoMs(): int
    {
        return $this->esperaRetryPadraoMs;
    }

    public function getTetoRetryAfterMs(): int
    {
        return $this->tetoRetryAfterMs;
    }
}
