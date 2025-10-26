<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram\Message;

use App\Application\Command\Telegram\AbstractTelegramCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;

class DeletedMessageCommand extends AbstractTelegramCommand implements TelegramCommandInterface
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
