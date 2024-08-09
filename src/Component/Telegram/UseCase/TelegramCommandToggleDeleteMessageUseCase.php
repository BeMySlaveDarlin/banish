<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatEntity;
use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Component\Telegram\ValueObject\ResponseMessages;
use App\Service\Doctrine\Type\JsonBValue;

readonly class TelegramCommandToggleDeleteMessageUseCase extends AbstractTelegramUseCase
{
    public function handleUpdate(TelegramChatEntity $chat, TelegramChatUserEntity $user): string
    {
        if (!$user->isAdmin) {
            return ResponseMessages::MESSAGE_NO_ACCESS;
        }

        $options = $chat->options->toArray();
        $option = !$options[TelegramChatEntity::OPTION_DELETE_MESSAGE];
        $options[TelegramChatEntity::OPTION_DELETE_MESSAGE] = $option;
        $chat->options = new JsonBValue($options);
        $this->entityManager->persist($chat);
        $this->entityManager->flush();

        $text = sprintf(ResponseMessages::MESSAGE_TOGGLE_DELETE_SPAM, $chat->name, $option ? 'On' : 'Off');
        $data = new TelegramSendMessage($this->update->getFrom()->id, $text);

        $message = $this->apiClientPolicy->sendMessage($data);
        if ($message && $message->message_id) {
            $this->apiClientPolicy->deleteMessage($chat->chatId, $this->update->message->message_id);
        }

        return $text;
    }
}
