<?php

declare(strict_types=1);

namespace App\Application\Handler\Admin;

use App\Application\Command\Telegram\Admin\SetMinMessagesForTrustCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Service\ChatConfigService;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\ResponseMessages;

class SetMinMessagesForTrustHandler implements TelegramHandlerInterface
{
    public function __construct(
        private ChatConfigService $chatConfigService,
        private TelegramApiService $telegramApiService,
        private string $botName
    ) {
    }

    /**
     * @param SetMinMessagesForTrustCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        if (!$command->user->isAdmin) {
            return ResponseMessages::MESSAGE_NO_ACCESS;
        }

        $input = $command->update->getMessageObj()->getCommand($this->botName);
        if ($input === null) {
            return ResponseMessages::MESSAGE_COMMAND_404;
        }

        $option = array_shift($input->options) ?? TelegramChatEntity::DEFAULT_MIN_MESSAGES_FOR_TRUST;
        $option = is_numeric($option) ? $option : TelegramChatEntity::DEFAULT_MIN_MESSAGES_FOR_TRUST;
        $minMessages = (int) $option;
        if ($minMessages < TelegramChatEntity::DEFAULT_MIN_MESSAGES_FOR_TRUST) {
            $minMessages = TelegramChatEntity::DEFAULT_MIN_MESSAGES_FOR_TRUST;
        }
        $this->chatConfigService->setMinMessagesForTrust($command->chat, $minMessages);

        $text = sprintf('Min messages for trust set to %d', $minMessages);
        $message = new TelegramSendMessage($command->update->getFrom()->id, $text);

        $sentMessage = $this->telegramApiService->sendMessage($message);
        if ($sentMessage && $sentMessage->message_id) {
            $this->telegramApiService->deleteMessage(
                $command->chat->chatId,
                $command->update->message->message_id
            );
        }

        return $text;
    }
}
