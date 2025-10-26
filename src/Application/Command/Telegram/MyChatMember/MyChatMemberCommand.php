<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram\MyChatMember;

use App\Application\Command\Telegram\AbstractTelegramCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;

class MyChatMemberCommand extends AbstractTelegramCommand implements TelegramCommandInterface
{
    public function getOldStatus(): ?string
    {
        return $this->update->my_chat_member?->old_chat_member?->status ?? null;
    }

    public function getNewStatus(): ?string
    {
        return $this->update->my_chat_member?->new_chat_member?->status ?? null;
    }

    public function getUserId(): int
    {
        return $this->update->my_chat_member?->from->id ?? 0;
    }

    public function getChatId(): int
    {
        return $this->update->my_chat_member?->chat->id ?? 0;
    }
}
