<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;

interface UserPersisterInterface
{
    public function persist(TelegramMessageChat $chat, TelegramMessageFrom $user): TelegramChatUserEntity;
}
