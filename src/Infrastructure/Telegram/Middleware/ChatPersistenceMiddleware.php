<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Middleware;

use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use Psr\Log\LoggerInterface;

class ChatPersistenceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ChatRepository $chatRepository,
        private UserRepository $userRepository,
        private TelegramApiService $telegramApiService
    ) {
    }

    public function handle(TelegramCommandInterface $command): TelegramCommandInterface
    {
        /** @var TelegramUpdate|null $update */
        $update = $command->update ?? null;
        if (!$update instanceof TelegramUpdate) {
            return $command;
        }

        $chat = $this->chatRepository->findByChatId($update->getChat()->id);
        if ($chat === null) {
            $chat = $this->chatRepository->createChat(
                $update->getChat()->id,
                $update->getChat()->type
            );
        }

        if (empty($chat->name)) {
            $chat->name = $update->getChat()->getAlias();
        }

        $this->chatRepository->save($chat);

        $user = $this->userRepository->findByChatAndUser(
            $update->getChat()->id,
            $update->getFrom()->id
        );

        if ($user === null) {
            $user = $this->userRepository->createUser(
                $update->getChat()->id,
                $update->getFrom()->id
            );
        }

        if (empty($user->name)) {
            $firstName = $update->getFrom()->first_name;
            $lastName = $update->getFrom()->last_name;
            $name = "$firstName $lastName";
            $user->name = trim($name);
        }

        if (empty($user->username)) {
            $user->username = $update->getFrom()->username;
        }

        $chatMember = $this->telegramApiService->getChatMember(
            $update->getChat()->id,
            $update->getFrom()->id
        );

        $user->isBot = $update->getFrom()->is_bot;
        $user->isAdmin = $chatMember && $chatMember->isAdmin();

        $this->userRepository->save($user);

        $command->chat = $chat;
        $command->user = $user;

        return $command;
    }
}
