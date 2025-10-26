<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramReactionType
{
    public string $type;
    public ?string $emoji = null;
}
