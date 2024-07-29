<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject;

use Symfony\Component\HttpFoundation\Request;

class TelegramUpdate
{
    public TelegramMessage $message;
    public int $update_id;
    public ?string $callback_query_id = null;
    public ?string $callback_query_data = null;
    public ?Request $request = null;

    public function isCallbackQuery(): bool
    {
        return $this->callback_query_id !== null && $this->callback_query_data !== null;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'update_id' => $this->update_id,
        ];
    }
}
