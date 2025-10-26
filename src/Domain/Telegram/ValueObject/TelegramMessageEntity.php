<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramMessageEntity
{
    public string $type;
    public int $length;
    public int $offset;
    /** @var array<string, mixed> */
    public array $user = [];
}
