<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class AsTelegramCommand
{
    public function __construct(
        public string $command
    ) {
    }
}
