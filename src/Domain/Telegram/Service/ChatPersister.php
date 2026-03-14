<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class ChatPersister implements ChatPersisterInterface
{
    public function __construct(
        private ChatRepository $chatRepository
    ) {
    }

    public function persist(TelegramMessageChat $chat): TelegramChatEntity
    {
        $chatId = $chat->id ?? 0;
        $existing = $this->chatRepository->findByChatId($chatId);

        if ($existing === null) {
            $chatType = $chat->type ?? '';
            try {
                $existing = $this->chatRepository->createChat($chatId, $chatType);
                $this->chatRepository->save($existing);
            } catch (UniqueConstraintViolationException) {
                $this->chatRepository->clear();
                $existing = $this->chatRepository->findByChatId($chatId);
                if ($existing === null) {
                    throw new \RuntimeException("Failed to persist chat $chatId");
                }
            }
        }

        if (empty($existing->name)) {
            $existing->name = $chat->getAlias();
        }

        $this->chatRepository->save($existing);

        return $existing;
    }
}
