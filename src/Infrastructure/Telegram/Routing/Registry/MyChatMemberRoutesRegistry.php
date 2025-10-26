<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\MyChatMember\MyChatMemberCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class MyChatMemberRoutesRegistry implements RouteRegistryInterface
{
    public function matches(TelegramUpdate $update, string $botName): bool
    {
        return $update->my_chat_member !== null;
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        return MyChatMemberCommand::class;
    }
}
