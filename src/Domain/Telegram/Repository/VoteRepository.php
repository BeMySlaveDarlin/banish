<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Repository;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.ban = :ban')
            ->andWhere('v.vote = :voteType')
            ->setParameter('ban', $ban)
            ->setParameter('voteType', $voteType)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getVotersByType(TelegramChatUserBanEntity $ban, VoteType $voteType): array
    {
        return $this->createQueryBuilder('v')
            ->select('v')
            ->where('v.ban = :ban')
            ->andWhere('v.vote = :voteType')
            ->setParameter('ban', $ban)
            ->setParameter('voteType', $voteType)
            ->getQuery()
            ->getResult();
    }

    public function save(TelegramChatUserBanVoteEntity $vote): void
    {
        $this->getEntityManager()->persist($vote);
        $this->getEntityManager()->flush();
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
}
