<?php

declare(strict_types=1);

namespace App\Application\Handler\MyChatMember;

use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Enum\UserStatus;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;

class MyChatMemberHandler implements TelegramHandlerInterface
{
    public function __construct(
        private readonly ChatRepository $chatRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    public function handle(TelegramCommandInterface $command): string
    {
        $oldStatus = $command->getOldStatus();
        $newStatus = $command->getNewStatus();
        $userId = $command->getUserId();
        $chatId = $command->getChatId();

        $chat = $this->chatRepository->findByChatId($chatId);
        if (!$chat) {
            return Messages::MESSAGE_BOT_DISABLED;
        }

        if ($oldStatus === 'left' && $newStatus === 'member') {
            return $this->handleUserJoined($chatId, $userId);
        }

        if ($oldStatus === 'member' && $newStatus === 'left') {
            return $this->handleUserLeft($chatId, $userId);
        }

        if ($oldStatus === 'member' && $newStatus === 'kicked') {
            return $this->handleUserBanned($chatId, $userId);
        }

        return Messages::MESSAGE_BOT_DISABLED;
    }

    private function handleUserJoined(int $chatId, int $userId): string
    {
        $existingUser = $this->userRepository->findByChatAndUser($chatId, $userId);

        if ($existingUser === null) {
            $user = $this->userRepository->createUser($chatId, $userId);
            $this->userRepository->save($user);

            return Messages::MESSAGE_BOT_DISABLED;
        }

        return Messages::MESSAGE_BOT_DISABLED;
    }

    private function handleUserLeft(int $chatId, int $userId): string
    {
        $user = $this->userRepository->findByChatAndUser($chatId, $userId);

        if ($user !== null) {
            $user->status = UserStatus::LEFT;
            $this->userRepository->save($user);
        }

        return Messages::MESSAGE_BOT_DISABLED;
    }

    private function handleUserBanned(int $chatId, int $userId): string
    {
        $user = $this->userRepository->findByChatAndUser($chatId, $userId);

        if ($user !== null) {
            $user->status = UserStatus::BANNED;
            $this->userRepository->save($user);
        }

        return Messages::MESSAGE_BOT_DISABLED;
    }
}
