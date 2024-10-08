<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject;

class TelegramCallbackQuery
{
    public ?string $id = null;
    public ?TelegramMessageFrom $from = null;
    public ?TelegramMessage $message = null;
    public ?string $data = null;
    public ?string $chat_instance = null;

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'id' => $this->id,
            'data' => $this->data,
            'chat_instance' => $this->chat_instance,
        ];
    }
}
