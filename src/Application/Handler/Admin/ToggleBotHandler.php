<?php

declare(strict_types=1);

namespace App\Application\Handler\Admin;

use App\Application\Command\Telegram\Admin\ToggleBotCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\ResponseMessages;

class ToggleBotHandler implements TelegramHandlerInterface
{
    public function __construct(
        private ChatRepository $chatRepository,
        private TelegramApiService $telegramApiService
    ) {
    }

    /**
     * @param ToggleBotCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        if (!$command->user->isAdmin) {
            return ResponseMessages::MESSAGE_NO_ACCESS;
        }

        $command->chat->isEnabled = !$command->chat->isEnabled;
        $this->chatRepository->save($command->chat);

        $text = sprintf(
            ResponseMessages::MESSAGE_BOT_STATUS,
            $command->chat->name,
            $command->chat->isEnabled ? 'Enabled' : 'Disabled'
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
