<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsTelegramHandler
{
    public function __construct(
        public string $commandClass
    ) {
    }
}
