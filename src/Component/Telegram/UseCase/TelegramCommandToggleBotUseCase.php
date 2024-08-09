<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatEntity;
use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Component\Telegram\ValueObject\ResponseMessages;

readonly class TelegramCommandToggleBotUseCase extends AbstractTelegramUseCase
{
    public function handleUpdate(TelegramChatEntity $chat, TelegramChatUserEntity $user): string
    {
        if (!$user->isAdmin) {
            return ResponseMessages::MESSAGE_NO_ACCESS;
        }

        $chat->isEnabled = !$chat->isEnabled;
        $this->entityManager->persist($chat);
        $this->entityManager->flush();

        $text = sprintf(ResponseMessages::MESSAGE_BOT_STATUS, $chat->name, $chat->isEnabled ? 'Enabled' : 'Disabled');
        $data = new TelegramSendMessage($this->update->getFrom()->id, $text);

        $message = $this->apiClientPolicy->sendMessage($data);
        if ($message && $message->message_id) {
            $this->apiClientPolicy->deleteMessage($chat->chatId, $this->update->message->message_id);
        }

        return $text;
    }
}
