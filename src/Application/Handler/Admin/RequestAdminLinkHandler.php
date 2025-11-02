<?php

declare(strict_types=1);

namespace App\Application\Handler\Admin;

use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;

class RequestAdminLinkHandler implements TelegramHandlerInterface
{
    public function __construct(
        private readonly AdminSessionService $sessionService,
        private readonly UserRepository $userRepository,
        private readonly TelegramApiService $telegramApiService,
        private readonly string $adminPanelBaseUrl,
    ) {
    }

    public function handle(TelegramCommandInterface $command): string
    {
        if (!$command->user->isAdmin) {
            return Messages::MESSAGE_NO_ACCESS;
        }

        $adminChats = $this->userRepository->findByUserIdAdminChats($command->user->userId);
        if (empty($adminChats)) {
            return Messages::MESSAGE_NO_ACCESS;
        }

        try {
            $session = $this->sessionService->getOrCreateSession($command->user->userId);

            $adminLink = sprintf(
                '%s/admin/auth/%s',
                rtrim($this->adminPanelBaseUrl, '/'),
                $session->id
            );

            $message = new TelegramSendMessage(
                $command->update->getFrom()->id,
                sprintf(
                    "🔐 <b>Admin Panel</b>\n\n" .
                    "You manage <b>%d chat(s)</b>\n\n" .
                    "Click to access:\n" .
                    "%s\n\n" .
                    "⏰ Link expires in 1 hour",
                    count($adminChats),
                    $adminLink
                )
            );
            $message->parse_mode = 'HTML';

            $sentMessage = $this->telegramApiService->sendMessage($message);

            $text = 'Admin link sent to private chat!';
        } catch (\Throwable $exception) {
            $sentMessage = false;
            $text = 'Failed to generate link. Try again later. ' . $exception->getMessage();
        }

        if ($sentMessage && $sentMessage->message_id) {
            $this->telegramApiService->deleteMessage((int) $command->chat->chatId, (int) $command->update->message->message_id);
        }

        return $text;
    }
}
