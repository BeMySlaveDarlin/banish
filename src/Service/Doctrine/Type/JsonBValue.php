<?php

declare(strict_types=1);

namespace App\Service\Doctrine\Type;

use JsonException;
use JsonSerializable;
use Stringable;

class JsonBValue implements JsonSerializable, Stringable
{
    public function __construct(
        public ?array $data = null
    ) {
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function get(mixed $key = null, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(mixed $key = null): bool
    {
        return isset($this->data[$key]);
    }

    public function set(mixed $key = null, mixed $value = null): void
    {
        if (null !== $key) {
            $this->data[$key] = $value;
        } else {
            $this->data[] = $value;
        }
    }

    public function jsonSerialize(): ?array
    {
        return $this->data;
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }
}
