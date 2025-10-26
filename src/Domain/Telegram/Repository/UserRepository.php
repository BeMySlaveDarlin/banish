<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Repository;

use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramChatUserEntity>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatUserEntity::class);
    }

    public function findByChatAndUser(int $chatId, ?int $userId): ?TelegramChatUserEntity
    {
        return $this->findOneBy([
            'chatId' => $chatId,
            'userId' => $userId,
        ]);
    }

    public function findById(int $userId): ?TelegramChatUserEntity
    {
        return $this->findOneBy([
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

    public function save(TelegramChatUserEntity $user, bool $flush = true): void
    {
        $this->getEntityManager()->persist($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
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

    /**
     * @return array<int, TelegramChatUserEntity>
     */
    public function findByUserIdAdminChats(int $userId): array
    {
        return $this
            ->createQueryBuilder('u')
            ->where('u.userId = :userId')
            ->andWhere('u.isAdmin = true')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function countByChat(int $chatId): int
    {
        return (int) $this
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, TelegramChatUserEntity>
     */
    public function findByChatWithPagination(int $chatId, int $limit, int $offset): array
    {
        return $this
            ->createQueryBuilder('u')
            ->where('u.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->orderBy('u.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function remove(TelegramChatUserEntity $user, bool $flush = true): void
    {
        $this->getEntityManager()->remove($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
