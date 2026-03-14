<?php

declare(strict_types=1);

namespace App\Application\Handler\MyChatMember;

use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Enum\UserStatus;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\UserPersisterInterface;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;
use App\Application\Command\Telegram\MyChatMember\MyChatMemberCommand;
use App\Infrastructure\Telegram\Attribute\AsTelegramHandler;

#[AsTelegramHandler(MyChatMemberCommand::class)]
final readonly class MyChatMemberHandler implements TelegramHandlerInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPersisterInterface $userPersister
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
            return Messages::MESSAGE_SILENT_OK;
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

        return Messages::MESSAGE_SILENT_OK;
    }

    private function handleUserJoined(TelegramMessageChat $chat, TelegramMessageFrom $user): string
    {
        $this->userPersister->persist($chat, $user);

        return Messages::MESSAGE_SILENT_OK;
    }

    private function handleUserLeft(int $chatId, int $userId): string
    {
        $user = $this->userRepository->findByChatAndUser($chatId, $userId);

        if ($user !== null) {
            $user->status = UserStatus::LEFT;
            $this->userRepository->save($user);
        }

        return Messages::MESSAGE_SILENT_OK;
    }

    private function handleUserBanned(int $chatId, int $userId): string
    {
        $user = $this->userRepository->findByChatAndUser($chatId, $userId);

        if ($user !== null) {
            $user->status = UserStatus::BANNED;
            $this->userRepository->save($user);
        }

        return Messages::MESSAGE_SILENT_OK;
    }
}
