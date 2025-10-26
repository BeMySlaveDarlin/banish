<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramMessageCommand
{
    /**
     * @param array<int, string> $options
     */
    public function __construct(
        public string $command,
        public array $options = []
    ) {
    }
}
