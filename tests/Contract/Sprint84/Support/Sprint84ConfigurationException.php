<?php

declare(strict_types=1);

namespace Tests\Contract\Sprint84\Support;

use InvalidArgumentException;
use Throwable;

final class Sprint84ConfigurationException extends InvalidArgumentException
{
    private readonly string $actualType;
    private readonly mixed $actualValue;
    private readonly bool $actualPresent;
    private readonly ?int $actualSize;

    public function __construct(
        private readonly string $errorCode,
        private readonly string $fieldPath,
        private readonly string $expected,
        mixed $actual,
        ?Throwable $previous = null
    ) {
        $this->actualType = get_debug_type($actual);
        $this->actualPresent = $actual !== null && $actual !== false;
        $this->actualSize = is_string($actual)
            ? strlen($actual)
            : (is_array($actual) ? count($actual) : null);
        $this->actualValue = $this->protect($fieldPath, $actual);

        parent::__construct(
            sprintf(
                '%s: campo %s esperava %s; recebeu %s (%s).',
                $errorCode,
                $fieldPath,
                $expected,
                $this->describe($this->actualValue),
                $this->actualType
            ),
            0,
            $previous
        );
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getFieldPath(): string
    {
        return $this->fieldPath;
    }

    public function getExpected(): string
    {
        return $this->expected;
    }

    public function getActualType(): string
    {
        return $this->actualType;
    }

    public function getActualValue(): mixed
    {
        return $this->actualValue;
    }

    /** @return array<string, mixed>|null */
    public function getProtectedActualMetadata(): ?array
    {
        if (!$this->isSensitive($this->fieldPath)) {
            return null;
        }

        return [
            'presente' => $this->actualPresent,
            'tipo' => $this->actualType,
            'tamanho' => $this->actualSize,
        ];
    }

    private function protect(string $fieldPath, mixed $actual): mixed
    {
        if ($this->isSensitive($fieldPath)) {
            return '[PROTEGIDO]';
        }

        if (is_object($actual)) {
            return $actual::class;
        }

        if (is_resource($actual)) {
            return get_resource_type($actual);
        }

        return $actual;
    }

    private function isSensitive(string $fieldPath): bool
    {
        return preg_match(
            '/(?:senha|password|token|jwt|authorization|credencial)/i',
            $fieldPath
        ) === 1;
    }

    private function describe(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? get_debug_type($value) : $encoded;
    }
}
