<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject;

use Symfony\Component\HttpFoundation\Request;

class TelegramUpdate
{
    public int $update_id;
    public ?TelegramMessage $message = null;
    public ?TelegramCallbackQuery $callback_query = null;
    public ?Request $request = null;

    public function getChat(): TelegramMessageChat
    {
        return $this->getMessage()->chat ?? new TelegramMessageChat();
    }

    public function getFrom(): TelegramMessageFrom
    {
        return $this->message->from ?? $this->callback_query->from ?? new TelegramMessageFrom();
    }

    public function getMessage(): TelegramMessage
    {
        return $this->message ?? $this->callback_query->message ?? new TelegramMessage();
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
