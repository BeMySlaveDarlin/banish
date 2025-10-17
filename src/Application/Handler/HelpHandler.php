<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\Telegram\HelpCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\ResponseMessages;
use Psr\Log\LoggerInterface;

class HelpHandler implements TelegramHandlerInterface
{
    private const array COMMANDS = [
        '/help' => "List of available commands.\nUsage: /help\n",
        '/toggleBot' => "Turn on/off bot for group.\nDefault: Off.\nUsage: /toggleBot\n",
        '/votesLimit' => "Set max number of votes to accept or revoke ban.\nDefault: 3.\nUsage: /votesLimit 5\n",
        '/toggleDeleteMessage' => "Delete spam message after ban accepted.\nDefault: On.\nUsage: /toggleDeleteMessage\n",
        '/setMinMessagesForTrust' => "Set minimal previous messages in chat for user to prevent ban.\nDefault: 10.\nUsage: /setMinMessagesForTrust 10\n",
    ];

    public function __construct(
        private TelegramApiService $telegramApiService,
        private LoggerInterface $logger,
        private string $botName
    ) {
    }

    /**
     * @param HelpCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        $this->logger->info('test help');
        if (!$command->user->isAdmin && !$command->update->getChat()->isPrivate()) {
            return ResponseMessages::MESSAGE_NO_ACCESS;
        }

        $commandObj = $command->update->getMessageObj()->getCommand($this->botName);
        if ($commandObj === null) {
            return ResponseMessages::MESSAGE_COMMAND_404;
        }

        $allowedCommands = ['/start', '/help'];
        if (in_array($commandObj->command, $allowedCommands, true)) {
            $texts = [
                sprintf(ResponseMessages::MESSAGE_HELLO, $command->user->getAlias()),
            ];

            foreach (self::COMMANDS as $cmd => $description) {
                $texts[] = "$cmd -- $description";
            }

            $text = implode("\n", $texts);
            $message = new TelegramSendMessage($command->chat->chatId, $text);
            $this->telegramApiService->sendMessage($message);

            return ResponseMessages::MESSAGE_PROCESSED;
        }

        return ResponseMessages::MESSAGE_IS_PRIVATE_CHAT;
    }
}
