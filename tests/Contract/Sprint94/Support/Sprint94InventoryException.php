<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint94\Support;

use RuntimeException;
use Throwable;

final class Sprint94InventoryException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
