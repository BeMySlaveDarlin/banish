<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\Ban\StartBanCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class BotMentionRoutesRegistry implements RouteRegistryInterface
{
    public function getPriority(): int
    {
        return 50;
    }

    public function matches(TelegramUpdate $update, string $botName): bool
    {
        return $update->getMessageObj()->hasBotMention($botName);
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        return StartBanCommand::class;
    }
}
