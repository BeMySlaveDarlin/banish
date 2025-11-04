<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

abstract class AbstractTelegramCommand
{
    public function __construct(
        public TelegramUpdate $update,
        public TelegramChatEntity $chat,
        public TelegramChatUserEntity $user,
    ) {
    }

    public function getUpdate(): TelegramUpdate
    {
        return $this->update;
    }

    public function getChatId(): int
    {
        return $this->chat->chatId;
    }

    public function getNewStatus(): ?string
    {
        return null;
    }

    public function getOldStatus(): ?string
    {
        return null;
    }

    public function getUserId(): int
    {
        return $this->user->userId;
    }
}
