<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramMyChatMember
{
    public int $date;
    /** @var array<string, mixed>|null */
    public ?array $entities = null;

    public TelegramMessageChat $chat;
    public TelegramMessageFrom $from;
    public ?TelegramMessageFrom $old_chat_member = null;
    public ?TelegramMessageFrom $new_chat_member = null;

    public function getOldChatMemberFrom(): ?TelegramMessageFrom
    {
        return $this->old_chat_member ?? null;
    }

    public function getJoinChatMemberFrom(): ?TelegramMessageFrom
    {
        return $this->new_chat_member ?? null;
    }
}
