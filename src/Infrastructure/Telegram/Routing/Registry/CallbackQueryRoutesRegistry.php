<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\Ban\VoteForBanCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class CallbackQueryRoutesRegistry implements RouteRegistryInterface
{
    public function matches(TelegramUpdate $update, string $botName): bool
    {
        return $update->hasCallbackQueryData();
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        return VoteForBanCommand::class;
    }
}
