<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramUpdate
{
    public int $update_id;
    public ?TelegramMessage $message = null;
    public ?TelegramMessage $edited_message = null;
    public ?TelegramCallbackQuery $callback_query = null;
    public ?TelegramMyChatMember $my_chat_member = null;
    public ?array $request = null;

    public function getChat(): TelegramMessageChat
    {
        return $this->getMessageObj()->chat ?? $this->my_chat_member->chat ?? new TelegramMessageChat();
    }

    public function getFrom(): TelegramMessageFrom
    {
        return $this->callback_query->from ?? $this->getMessageObj()->from ?? $this->my_chat_member->from ?? new TelegramMessageFrom();
    }

    public function getMessageObj(): TelegramMessage
    {
        return $this->callback_query->message ?? $this->edited_message ?? $this->message ?? new TelegramMessage();
    }

    public function hasCallbackQueryData(): bool
    {
        return $this->callback_query !== null;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessageObj(),
            'update_id' => $this->update_id,
        ];
    }
}
