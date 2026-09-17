<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use RuntimeException;
use Throwable;

final class Sprint84GateException extends RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        private readonly string $errorCode,
        private readonly string $phase,
        string $message,
        private readonly array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getPhase(): string
    {
        return $this->phase;
    }

    /** @return array<string, mixed> */
    public function getDetails(): array
    {
        return $this->details;
    }
}
