<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramMessageReaction
{
    public TelegramMessageChat $chat;
    public TelegramMessageFrom $user;
    public ?int $message_id = null;
    public int $date = 0;

    /**
     * @var TelegramReactionType[]
     */
    public array $old_reaction = [];

    /**
     * @var TelegramReactionType[]
     */
    public array $new_reaction = [];

    public function hasNewReaction(): bool
    {
        return !empty($this->new_reaction);
    }

    public function getNewEmoji(): ?string
    {
        if (empty($this->new_reaction)) {
            return null;
        }

        $first = $this->new_reaction[0];

        return $first->emoji ?? null;
    }
}
