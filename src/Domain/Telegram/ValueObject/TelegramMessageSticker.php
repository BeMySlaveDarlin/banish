<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramMessageSticker
{
    public string $type;
    public string $emoji;
}
