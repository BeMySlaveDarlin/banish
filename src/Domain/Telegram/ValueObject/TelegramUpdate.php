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
    public ?TelegramMessageReaction $message_reaction = null;
    public ?TelegramDeletedMessage $message_deleted_by_user = null;
    /** @var array<string, mixed>|null */
    public ?array $request = null;

    public function getChat(): TelegramMessageChat
    {
        return $this->message_reaction->chat ?? $this->getMessageObj()->chat ?? $this->my_chat_member->chat ?? new TelegramMessageChat();
    }

    public function getFrom(): TelegramMessageFrom
    {
        return $this->message_reaction->user ?? $this->callback_query->from ?? $this->getMessageObj()->from ?? $this->my_chat_member->from ?? new TelegramMessageFrom();
    }

    public function getMessageObj(): TelegramMessage
    {
        return $this->callback_query->message ?? $this->edited_message ?? $this->message ?? new TelegramMessage();
    }

    public function hasCallbackQueryData(): bool
    {
        return $this->callback_query !== null;
    }

    public function getMessageId(): ?int
    {
        if ($this->message_reaction !== null) {
            return $this->message_reaction->message_id;
        }

        return $this->getMessageObj()->message_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessageObj(),
            'update_id' => $this->update_id,
        ];
    }
}
