<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram\Message;

use App\Application\Command\Telegram\AbstractTelegramCommand;

final class DeletedMessageCommand extends AbstractTelegramCommand
{
    public function getChatId(): int
    {
        return $this->update->message_deleted_by_user?->chat->id ?? 0;
    }

    public function getMessageId(): int
    {
        return $this->update->message_deleted_by_user?->message_id ?? 0;
    }
}
