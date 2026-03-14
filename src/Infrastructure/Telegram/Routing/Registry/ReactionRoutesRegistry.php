<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\Reaction\ReactionRemovedCommand;
use App\Application\Command\Telegram\Reaction\StartBanByReactionCommand;
use App\Application\Command\Telegram\Reaction\VoteByReactionCommand;
use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class ReactionRoutesRegistry implements RouteRegistryInterface
{
    public function matches(TelegramUpdate $update, string $botName): bool
    {
        return $update->message_reaction !== null;
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        if ($update->message_reaction === null) {
            return UnsupportedCommand::class;
        }

        $emoji = $update->message_reaction->getNewEmoji();
        if ($emoji === null) {
            return ReactionRemovedCommand::class;
        }

        return StartBanByReactionCommand::class;
    }

    public static function getPriority(): int
    {
        return 80;
    }
}
