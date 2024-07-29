<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject;

class TelegramMessageEntity
{
    public string $type;
    public int $length;
    public int $offset;
}
