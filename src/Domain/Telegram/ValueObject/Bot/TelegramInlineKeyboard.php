<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject\Bot;

use JsonSerializable;

class TelegramInlineKeyboard implements JsonSerializable
{
    /** @var array<int, array<string, string>> */
    public array $buttons = [];

    public function addButton(
        string $text,
        ?string $url = null,
        ?string $callbackData = null
    ): void {
        $button = [
            'text' => $text,
        ];
        if (null !== $url) {
            $button['url'] = $url;
        }
        if (null !== $callbackData) {
            $button['callback_data'] = $callbackData;
        }

        $this->buttons[] = $button;
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    public function jsonSerialize(): array
    {
        return [$this->buttons];
    }
}
