<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatEntity;
use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\ValueObject\ResponseMessages;

readonly class TelegramUnsupportedUseCase extends AbstractTelegramUseCase
{
    public function handleUpdate(TelegramChatEntity $chat, TelegramChatUserEntity $user): string
    {
        return ResponseMessages::MESSAGE_NOT_SUPPORTED_UPD;
    }
}
