<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Repository;

use App\Domain\Telegram\Entity\TelegramRequestHistoryEntity;
use App\Domain\Telegram\ValueObject\TelegramMessageReaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramRequestHistoryEntity>
 */
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

    public function findMessageByReaction(TelegramMessageReaction $reaction): ?TelegramRequestHistoryEntity
    {
        return $this
            ->createQueryBuilder('h')
            ->where('h.chatId = :chatId')
            ->andWhere('h.messageId = :messageId')
            ->setParameter('chatId', $reaction->chat->id)
            ->setParameter('messageId', $reaction->message_id)
            ->orderBy('h.updateId', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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

    /**
     * @return array<int, int>
     */
    public function getMessageIdsByFromId(int $chatId, int $fromId, ?\DateTimeImmutable $since = null): array
    {
        $qb = $this
            ->createQueryBuilder('h')
            ->select('h.messageId')
            ->where('h.chatId = :chatId')
            ->andWhere('h.fromId = :fromId')
            ->setParameter('chatId', $chatId)
            ->setParameter('fromId', $fromId);

        if ($since !== null) {
            $qb
                ->andWhere('h.createdAt >= :since')
                ->setParameter('since', $since);
        }

        $result = $qb->getQuery()->getResult();

        return array_column($result, 'messageId');
    }

    public function save(TelegramRequestHistoryEntity $history, bool $flush = true): void
    {
        $this->getEntityManager()->persist($history);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPreviousMessage(
        int $chatId,
        int $fromId,
        int $currentMessageId
    ): ?TelegramRequestHistoryEntity {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(TelegramRequestHistoryEntity::class, 'h');

        $sql = '
            SELECT h.*
            FROM telegram_request_history h
            WHERE h.chat_id = :chatId
            AND h.from_id != :fromId
            AND h.message_id < :messageId
            AND JSONB_EXISTS(h.request, :key)
            ORDER BY h.message_id DESC
            LIMIT 1
        ';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('chatId', $chatId);
        $query->setParameter('fromId', $fromId);
        $query->setParameter('messageId', $currentMessageId);
        $query->setParameter('key', 'message');

        return $query->getOneOrNullResult();
    }

    /**
     * @param array<string, mixed>|null $request
     */
    public function createHistory(
        int $chatId,
        int $fromId,
        int $messageId,
        int $updateId,
        ?array $request = null,
        mixed $response = null,
    ): TelegramRequestHistoryEntity {
        $history = new TelegramRequestHistoryEntity();
        $history->chatId = $chatId;
        $history->fromId = $fromId;
        $history->messageId = $messageId;
        $history->updateId = $updateId;
        if ($request !== null) {
            $history->setRequest($request);
        }
        $history->setResponse($response);
        $history->isNew = true;

        $this->getEntityManager()->persist($history);
        $this->getEntityManager()->flush();

        return $history;
    }

    public function findByChatAndMessageId(int $chatId, int $messageId): ?TelegramRequestHistoryEntity
    {
        return $this
            ->createQueryBuilder('h')
            ->where('h.chatId = :chatId')
            ->andWhere('h.messageId = :messageId')
            ->setParameter('chatId', $chatId)
            ->setParameter('messageId', $messageId)
            ->orderBy('h.updateId', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function markMessageDeleted(int $chatId, int $messageId): void
    {
        $message = $this->findByChatAndMessageId($chatId, $messageId);
        if ($message !== null) {
            $message->markAsDeleted();
            $this->save($message);
        }
    }
}
