<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject;

use Symfony\Component\HttpFoundation\Request;

class TelegramUpdate
{
    public int $update_id;
    public ?TelegramMessage $message = null;
    public ?TelegramMessage $edited_message = null;
    public ?TelegramCallbackQuery $callback_query = null;
    public ?TelegramMyChatMember $my_chat_member = null;
    public ?Request $request = null;

    public function getChat(): TelegramMessageChat
    {
        return $this->getMessage()->chat ?? $this->my_chat_member->chat ?? new TelegramMessageChat();
    }

    public function getFrom(): TelegramMessageFrom
    {
        return $this->callback_query->from ?? $this->getMessage()->from ?? $this->my_chat_member->from ?? new TelegramMessageFrom();
    }

    public function getMessage(): TelegramMessage
    {
        return $this->callback_query->message ?? $this->edited_message ?? $this->message ?? new TelegramMessage();
    }

    public function isCallbackQuery(): bool
    {
        return $this->callback_query !== null;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'update_id' => $this->update_id,
        ];
    }
}
