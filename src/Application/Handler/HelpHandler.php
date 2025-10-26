<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\Telegram\HelpCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;

class HelpHandler implements TelegramHandlerInterface
{
    private const array COMMANDS = [
        '/ban' => ['Start ban process', '—', '/ban'],
        '/help' => ['List of available commands', '—', '/help'],
        '/admin' => ['Get admin page link', '—', '/admin'],
    ];

    public function __construct(
        private readonly TelegramApiService $telegramApiService,
        private readonly string $botName
    ) {
    }

    /**
     * @param HelpCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        if ($command->user === null) {
            return Messages::MESSAGE_COMMAND_404;
        }

        $commandObj = $command->update->getMessageObj()->getCommand($this->botName);
        if ($commandObj === null) {
            return Messages::MESSAGE_COMMAND_404;
        }

        $helpCommands = ['/start', '/help'];
        $publicCommands = ['/start', '/help', '/ban'];
        if (in_array($commandObj->command, $helpCommands, true)) {
            $table = sprintf(Messages::MESSAGE_HELLO, $command->user->getAlias()) . "\n\n";
            $table .= "<pre>";
            $table .= "Command                Description\n";
            $table .= str_repeat("─", 65) . "\n";

            foreach (self::COMMANDS as $cmd => [$description, $default, $usage]) {
                if (!in_array($cmd, $publicCommands) && !$command->update->getChat()->isPrivate()) {
                    continue;
                }
                $cmd_padded = str_pad($cmd, 24);
                $table .= $cmd_padded . $description . "\n";
                $table .= "  Usage: " . $usage . "\n";
                $table .= "  Default: " . $default . "\n";
                $table .= str_repeat("─", 65) . "\n";
            }

            $table .= "</pre>";

            if ($command->chat === null) {
                return Messages::MESSAGE_BOT_DISABLED;
            }

            $message = new TelegramSendMessage($command->chat->chatId, $table);
            $message->parse_mode = 'HTML';
            $this->telegramApiService->sendMessage($message);

            return Messages::MESSAGE_PROCESSED;
        }

        return Messages::MESSAGE_IS_PRIVATE_CHAT;
    }
}
