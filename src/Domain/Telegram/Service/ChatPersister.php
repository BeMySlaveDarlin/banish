<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;

class ChatPersister
{
    public function __construct(
        private readonly ChatRepository $chatRepository
    ) {
    }

    public function persist(TelegramMessageChat $chat): TelegramChatEntity
    {
        $chatId = $chat->id ?? 0;
        $existing = $this->chatRepository->findByChatId($chatId);

        if ($existing === null) {
            $chatType = $chat->type ?? '';
            $existing = $this->chatRepository->createChat($chatId, $chatType);
        }

        if (empty($existing->name)) {
            $existing->name = $chat->getAlias();
        }

        $this->chatRepository->save($existing);

        return $existing;
    }
}
