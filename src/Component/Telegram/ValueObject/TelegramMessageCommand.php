<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject;

class TelegramMessageCommand
{
    public function __construct(
        public string $command,
        public array $options = []
    ) {
    }

}
