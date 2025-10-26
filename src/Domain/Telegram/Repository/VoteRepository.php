<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Repository;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramChatUserBanVoteEntity>
 */
class VoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatUserBanVoteEntity::class);
    }

    public function findByUserAndBan(
        TelegramChatUserEntity $user,
        TelegramChatUserBanEntity $ban
    ): ?TelegramChatUserBanVoteEntity {
        return $this->findOneBy([
            'user' => $user,
            'ban' => $ban,
        ]);
    }

    public function countVotesByType(TelegramChatUserBanEntity $ban, VoteType $voteType): int
    {
        return (int) $this
            ->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.ban = :ban')
            ->andWhere('v.vote = :voteType')
            ->setParameter('ban', $ban)
            ->setParameter('voteType', $voteType)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param TelegramChatUserBanEntity $ban
     * @param VoteType $voteType
     *
     * @return TelegramChatUserBanVoteEntity[]
     */
    public function getVotersByType(TelegramChatUserBanEntity $ban, VoteType $voteType): array
    {
        return $this
            ->createQueryBuilder('v')
            ->select('v')
            ->where('v.ban = :ban')
            ->andWhere('v.vote = :voteType')
            ->setParameter('ban', $ban)
            ->setParameter('voteType', $voteType)
            ->getQuery()
            ->getResult();
    }

    public function save(TelegramChatUserBanVoteEntity $vote, bool $flush = true): void
    {
        $this->getEntityManager()->persist($vote);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function delete(TelegramChatUserBanVoteEntity $vote, bool $flush = true): void
    {
        $this->getEntityManager()->remove($vote);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function createVote(
        TelegramChatUserEntity $user,
        TelegramChatUserBanEntity $ban,
        int $chatId,
        VoteType $voteType
    ): TelegramChatUserBanVoteEntity {
        $vote = new TelegramChatUserBanVoteEntity();
        $vote->user = $user;
        $vote->ban = $ban;
        $vote->chatId = $chatId;
        $vote->vote = $voteType;

        return $vote;
    }

    public function countByChat(int $chatId): int
    {
        return (int) $this
            ->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteByBan(TelegramChatUserBanEntity $ban, bool $flush = true): void
    {
        $this
            ->createQueryBuilder('v')
            ->delete()
            ->where('v.ban = :ban')
            ->setParameter('ban', $ban)
            ->getQuery()
            ->execute();

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
