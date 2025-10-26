<?php

declare(strict_types=1);

namespace App\Application\Message;

final class TelegramUpdateMessage
{
    public function __construct(
        public readonly string $updateJson,
    ) {
    }
}
