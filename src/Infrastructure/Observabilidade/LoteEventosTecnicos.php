<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Observabilidade;

use InvalidArgumentException;

final class LoteEventosTecnicos
{
    /** @var array<int, array<string, mixed>> */
    private readonly array $envelopes;

    /** @param EventoTecnico[] $eventos */
    public function __construct(array $eventos, private readonly int $executionTimeMs)
    {
        if ($this->executionTimeMs < 0) {
            throw new InvalidArgumentException('Tempo de execução não pode ser negativo.');
        }

        $envelopes = [];

        foreach ($eventos as $evento) {
            if (!$evento instanceof EventoTecnico) {
                throw new InvalidArgumentException('Lote contém evento técnico inválido.');
            }

            $envelopes[] = $evento->paraEnvelope();
        }

        if ($envelopes !== []) {
            $ultimo = array_key_last($envelopes);
            $metadata = $envelopes[$ultimo]['metadata'];
            $metadata['executionTimeMs'] = $this->executionTimeMs;
            $envelopes[$ultimo]['metadata'] = $metadata;
        }

        $this->envelopes = $envelopes;
    }

    /** @return array<int, array<string, mixed>> */
    public function getEnvelopes(): array
    {
        return $this->envelopes;
    }

    public function getExecutionTimeMs(): int
    {
        return $this->executionTimeMs;
    }
}
