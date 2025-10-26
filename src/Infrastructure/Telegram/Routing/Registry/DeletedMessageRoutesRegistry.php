<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\Message\DeletedMessageCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class DeletedMessageRoutesRegistry implements RouteRegistryInterface
{
    public function matches(TelegramUpdate $update, string $botName): bool
    {
        return $update->message_deleted_by_user !== null;
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        return DeletedMessageCommand::class;
    }
}
