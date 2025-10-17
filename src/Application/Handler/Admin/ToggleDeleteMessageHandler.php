<?php

declare(strict_types=1);

namespace App\Application\Handler\Admin;

use App\Application\Command\Telegram\Admin\ToggleDeleteMessageCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Service\ChatConfigService;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\ResponseMessages;

class ToggleDeleteMessageHandler implements TelegramHandlerInterface
{
    public function __construct(
        private ChatConfigService $chatConfigService,
        private TelegramApiService $telegramApiService
    ) {
    }

    /**
     * @param ToggleDeleteMessageCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        if (!$command->user->isAdmin) {
            return ResponseMessages::MESSAGE_NO_ACCESS;
        }

        $currentValue = $this->chatConfigService->isDeleteMessagesEnabled($command->chat);
        $newValue = !$currentValue;

        $this->chatConfigService->setDeleteMessagesEnabled($command->chat, $newValue);

        $text = sprintf(
            ResponseMessages::MESSAGE_DELETE_MESSAGE_STATUS,
            $newValue ? 'Enabled' : 'Disabled'
        );

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
