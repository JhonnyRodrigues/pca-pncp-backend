<?php

declare(strict_types=1);

namespace Semae\Pca\Infrastructure\Observabilidade;

use DateTimeImmutable;
use DateTimeInterface;

final class EventoTecnico
{
    private const SISTEMA = 'pca-pncp-backend';

    /**
     * @param array<string, mixed> $error
     * @param array<string, mixed> $context
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly DateTimeImmutable $timestamp,
        private readonly NivelEventoTecnico $level,
        private readonly string $correlationId,
        private readonly string $environment,
        private readonly string $module,
        private readonly string $action,
        private readonly string $message,
        private readonly array $error,
        private readonly array $context,
        private readonly array $metadata,
        private readonly ?string $ipAddress
    ) {}

    public function getTimestamp(): string
    {
        return $this->timestamp->format(DateTimeInterface::ATOM);
    }

    public function getLevel(): string
    {
        return $this->level->value;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /** @return array<string, mixed> */
    public function paraEnvelope(): array
    {
        return [
            'timestamp' => $this->getTimestamp(),
            'level' => $this->level->value,
            'correlationId' => $this->correlationId,
            'environment' => $this->environment,
            'system' => self::SISTEMA,
            'module' => $this->module,
            'action' => $this->action,
            'message' => $this->message,
            'error' => $this->error,
            'context' => $this->context,
            'metadata' => $this->metadata,
            'ipAddress' => $this->ipAddress,
        ];
    }
}
