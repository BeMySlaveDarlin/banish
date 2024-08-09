<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatEntity;
use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Component\Telegram\ValueObject\ResponseMessages;
use App\Service\Doctrine\Type\JsonBValue;

readonly class TelegramCommandSetVotesLimitUseCase extends AbstractTelegramUseCase implements TelegramCommandUseCaseInterface
{
    public function handleUpdate(TelegramChatEntity $chat, TelegramChatUserEntity $user): string
    {
        if (!$chat->isEnabled) {
            return ResponseMessages::MESSAGE_BOT_DISABLED;
        }

        if (!$user->isAdmin) {
            return ResponseMessages::MESSAGE_NO_ACCESS;
        }

        $command = $this->update->getMessage()->getCommand($this->configPolicy->botName);
        if (null === $command) {
            return ResponseMessages::MESSAGE_COMMAND_404;
        }

        $options = $chat->options->toArray();
        $option = array_shift($command->options) ?? TelegramChatEntity::DEFAULT_VOTES_REQUIRED;
        $option = is_numeric($option) ? $option : TelegramChatEntity::DEFAULT_VOTES_REQUIRED;
        if ($options < TelegramChatEntity::DEFAULT_VOTES_REQUIRED) {
            return sprintf(ResponseMessages::MESSAGE_VOTE_MIN_LIMIT, TelegramChatEntity::DEFAULT_VOTES_REQUIRED);
        }

        $options[TelegramChatEntity::OPTION_BAN_VOTES_REQUIRED] = $option;
        $chat->options = new JsonBValue($options);
        $this->entityManager->persist($chat);
        $this->entityManager->flush();

        $text = sprintf(ResponseMessages::MESSAGE_VOTE_MAX_LIMIT, $chat->name, $option);
        $data = new TelegramSendMessage($this->update->getFrom()->id, $text);

        $message = $this->apiClientPolicy->sendMessage($data);
        if ($message && $message->message_id) {
            $this->apiClientPolicy->deleteMessage($chat->chatId, $this->update->message->message_id);
        }

        return $text;
    }
}
