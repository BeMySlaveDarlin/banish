<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Repository;

use App\Domain\Telegram\Entity\TelegramRequestHistoryEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RequestHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramRequestHistoryEntity::class);
    }

    public function findByUpdate(
        int $chatId,
        int $fromId,
        int $messageId,
        int $updateId
    ): ?TelegramRequestHistoryEntity {
        return $this->findOneBy([
            'chatId' => $chatId,
            'fromId' => $fromId,
            'messageId' => $messageId,
            'updateId' => $updateId,
        ]);
    }

    public function countMessagesByFromId(int $chatId, int $fromId): int
    {
        return (int) $this
            ->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->where('h.chatId = :chatId')
            ->andWhere('h.fromId = :fromId')
            ->setParameter('chatId', $chatId)
            ->setParameter('fromId', $fromId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(TelegramRequestHistoryEntity $history): void
    {
        $this->getEntityManager()->persist($history);
        $this->getEntityManager()->flush();
    }

    public function findPreviousMessage(
        int $chatId,
        int $fromId,
        int $currentMessageId
    ): ?TelegramRequestHistoryEntity {
        return $this
            ->createQueryBuilder('h')
            ->where('h.chatId = :chatId')
            ->andWhere('h.fromId = :fromId')
            ->andWhere('h.messageId < :messageId')
            ->setParameter('chatId', $chatId)
            ->setParameter('fromId', $fromId)
            ->setParameter('messageId', $currentMessageId)
            ->orderBy('h.messageId', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createHistory(
        int $chatId,
        int $fromId,
        int $messageId,
        int $updateId,
        array $request,
        mixed $response = null,
    ): TelegramRequestHistoryEntity {
        $history = new TelegramRequestHistoryEntity();
        $history->chatId = $chatId;
        $history->fromId = $fromId;
        $history->messageId = $messageId;
        $history->updateId = $updateId;
        $history->setRequest($request);
        $history->setResponse($response);
        $history->isNew = true;

        $this->getEntityManager()->persist($history);
        $this->getEntityManager()->flush();

        return $history;
    }
}
