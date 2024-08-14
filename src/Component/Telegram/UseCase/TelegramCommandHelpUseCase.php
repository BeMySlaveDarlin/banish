<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatEntity;
use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Component\Telegram\ValueObject\ResponseMessages;

readonly class TelegramCommandHelpUseCase extends AbstractTelegramUseCase
{
    public function handleUpdate(TelegramChatEntity $chat, TelegramChatUserEntity $user): string
    {
        if (!$user->isAdmin && !$this->update->getChat()->isPrivate()) {
            return ResponseMessages::MESSAGE_NO_ACCESS;
        }

        $command = $this->update->getMessageObj()->getCommand($this->configPolicy->botName);
        if (null === $command) {
            return ResponseMessages::MESSAGE_COMMAND_404;
        }

        $allowedCommands = [TelegramCommandUseCaseInterface::COMMAND_START, TelegramCommandUseCaseInterface::COMMAND_HELP];
        if (in_array($command->command, $allowedCommands, true)) {
            $texts = [
                sprintf(ResponseMessages::MESSAGE_HELLO, $user->getAlias()),
            ];

            foreach (TelegramCommandUseCaseInterface::COMMANDS_MAP as $command => $params) {
                $texts[] = "$command -- {$params['description']}";
            }

            $text = implode("\n", $texts);
            $data = new TelegramSendMessage($chat->chatId, $text);
            $this->apiClientPolicy->sendMessage($data);

            return ResponseMessages::MESSAGE_PROCESSED;
        }

        return ResponseMessages::MESSAGE_IS_PRIVATE_CHAT;
    }
}
