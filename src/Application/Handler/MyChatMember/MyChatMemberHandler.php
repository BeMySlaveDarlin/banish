<?php

declare(strict_types=1);

namespace App\Application\Handler\MyChatMember;

use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Enum\UserStatus;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\UserPersister;

class MyChatMemberHandler implements TelegramHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPersister $userPersister
    ) {
    }

    public function handle(TelegramCommandInterface $command): string
    {
        $oldStatus = $command->getOldStatus();
        $newStatus = $command->getNewStatus();
        $userId = $command->getUserId();
        $chatId = $command->getChatId();

        $myChatMember = $command->update->my_chat_member;
        if (!$myChatMember) {
            return Messages::MESSAGE_BOT_DISABLED;
        }

        if ($oldStatus === 'left' && $newStatus === 'member') {
            return $this->handleUserJoined($myChatMember->chat, $myChatMember->from);
        }

        if ($oldStatus === 'member' && $newStatus === 'left') {
            return $this->handleUserLeft($chatId, $userId);
        }

        if ($oldStatus === 'member' && $newStatus === 'kicked') {
            return $this->handleUserBanned($chatId, $userId);
        }

        return Messages::MESSAGE_BOT_DISABLED;
    }

    private function handleUserJoined($chat, $user): string
    {
        $this->userPersister->persist($chat, $user);

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
