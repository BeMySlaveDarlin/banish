<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\VoteRepository;

class VoteService
{
    public function __construct(
        private readonly VoteRepository $voteRepository,
        private readonly ChatConfigServiceInterface $chatConfigService
    ) {
    }

    public function vote(
        TelegramChatEntity $chat,
        TelegramChatUserEntity $user,
        TelegramChatUserBanEntity $ban,
        VoteType $voteType
    ): TelegramChatUserBanVoteEntity {
        $vote = $this->voteRepository->findByUserAndBan($user, $ban);

        if ($vote === null) {
            $vote = $this->voteRepository->createVote($user, $ban, $chat->chatId, $voteType);
        } else {
            $vote->vote = $voteType;
        }

        $this->voteRepository->save($vote);

        return $vote;
    }

    /**
     * @return array{
     *     upVotes: array<int, TelegramChatUserEntity>,
     *     downVotes: array<int, TelegramChatUserEntity>,
     *     upCount: int,
     *     downCount: int,
     *     requiredVotes: int,
     *     shouldBan: bool,
     *     shouldForgive: bool
     * }
     */
    public function getVoteResult(
        TelegramChatEntity $chat,
        TelegramChatUserBanEntity $ban
    ): array {
        $upVotes = $this->voteRepository->getVotersByType($ban, VoteType::BAN);
        $downVotes = $this->voteRepository->getVotersByType($ban, VoteType::FORGIVE);

        $upVoters = array_filter(
            array_map(static fn($vote) => $vote->user, $upVotes),
            static fn($user) => $user !== null
        );
        $downVoters = array_filter(
            array_map(static fn($vote) => $vote->user, $downVotes),
            static fn($user) => $user !== null
        );

        $requiredVotes = $this->chatConfigService->getVotesRequired($chat);

        return [
            'upVotes' => array_values($upVoters),
            'downVotes' => array_values($downVoters),
            'upCount' => count($upVoters),
            'downCount' => count($downVoters),
            'requiredVotes' => $requiredVotes,
            'shouldBan' => count($upVoters) >= $requiredVotes,
            'shouldForgive' => count($downVoters) >= $requiredVotes,
        ];
    }
}
