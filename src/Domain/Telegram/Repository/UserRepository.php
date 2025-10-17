<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Repository;

use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatUserEntity::class);
    }

    public function findByChatAndUser(int $chatId, int $userId): ?TelegramChatUserEntity
    {
        return $this->findOneBy([
            'chatId' => $chatId,
            'userId' => $userId,
        ]);
    }

    public function findByChatAndUsername(int $chatId, string $name): ?TelegramChatUserEntity
    {
        return $this
            ->createQueryBuilder('u')
            ->where('u.chatId = :chatId')
            ->andWhere('(u.username = :username OR u.name = :username)')
            ->setParameter('chatId', $chatId)
            ->setParameter('username', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(TelegramChatUserEntity $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function createUser(int $chatId, int $userId): TelegramChatUserEntity
    {
        $user = new TelegramChatUserEntity();
        $user->chatId = $chatId;
        $user->userId = $userId;
        $user->isBot = false;
        $user->isAdmin = false;

        return $user;
    }
}
