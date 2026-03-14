<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class UserPersister implements UserPersisterInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function persist(TelegramMessageChat $chat, TelegramMessageFrom $user): TelegramChatUserEntity
    {
        $chatId = $chat->id ?? 0;
        $userId = $user->id ?? 0;

        $existing = $this->userRepository->findByChatAndUser($chatId, $userId);
        if ($existing === null) {
            try {
                $existing = $this->userRepository->createUser($chatId, $userId);
                $this->userRepository->save($existing);
            } catch (UniqueConstraintViolationException) {
                $this->userRepository->clear();
                $existing = $this->userRepository->findByChatAndUser($chatId, $userId);
                if ($existing === null) {
                    throw new \RuntimeException("Failed to persist user $userId in chat $chatId");
                }
            }
        }

        if (empty($existing->name)) {
            $firstName = $user->first_name;
            $lastName = $user->last_name;
            $name = "$firstName $lastName";
            $existing->name = trim($name);
        }
        if (empty($existing->username)) {
            $existing->username = $user->username;
        }
        $existing->isBot = $user->is_bot;

        $this->userRepository->save($existing);

        return $existing;
    }
}
