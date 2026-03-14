<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;

interface ChatPersisterInterface
{
    public function persist(TelegramMessageChat $chat): TelegramChatEntity;
}
