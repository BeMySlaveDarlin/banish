<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;

class UserPersister
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TelegramApiService $telegramApiService
    ) {
    }

    public function persist(TelegramMessageChat $chat, TelegramMessageFrom $user): TelegramChatUserEntity
    {
        $chatId = $chat->id ?? 0;
        $userId = $user->id ?? 0;

        $existing = $this->userRepository->findByChatAndUser($chatId, $userId);
        if ($existing === null) {
            $existing = $this->userRepository->createUser($chatId, $userId);
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

        $chatMember = $this->telegramApiService->getChatMember($chatId, $userId);
        $existing->isAdmin = $chatMember && $chatMember->isAdmin();
        $this->userRepository->save($existing);

        return $existing;
    }

}
