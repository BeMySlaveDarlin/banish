<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Domain\Telegram\ValueObject\TelegramUpdate;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.route_registry')]
interface RouteRegistryInterface
{
    public function matches(TelegramUpdate $update, string $botName): bool;

    public function getCommand(TelegramUpdate $update, string $botName): string;

    public static function getPriority(): int;
}
