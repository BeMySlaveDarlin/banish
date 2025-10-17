<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Repository;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatEntity::class);
    }

    public function findByChatId(int $chatId): ?TelegramChatEntity
    {
        return $this->findOneBy(['chatId' => $chatId]);
    }

    public function save(TelegramChatEntity $chat): void
    {
        $this->getEntityManager()->persist($chat);
        $this->getEntityManager()->flush();
    }

    public function createChat(int $chatId, string $type): TelegramChatEntity
    {
        $chat = new TelegramChatEntity();
        $chat->chatId = $chatId;
        $chat->type = $type;
        $chat->isEnabled = false;

        return $chat;
    }
}
