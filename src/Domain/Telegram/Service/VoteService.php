<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\ValueObject\VoteResult;

final readonly class VoteService implements VoteServiceInterface
{
    public function __construct(
        private VoteRepository $voteRepository,
        private ChatConfigServiceInterface $chatConfigService
    ) {
    }

    public function vote(
        TelegramChatEntity $chat,
        TelegramChatUserEntity $user,
        TelegramChatUserBanEntity $ban,
        VoteType $voteType
    ): ?TelegramChatUserBanVoteEntity {
        if ($user->userId === $ban->spammerId) {
            return null;
        }

        $vote = $this->voteRepository->findByUserAndBan($user, $ban);

        if ($vote === null) {
            $vote = $this->voteRepository->createVote($user, $ban, $chat->chatId, $voteType);
        } else {
            $vote->vote = $voteType;
        }

        $this->voteRepository->save($vote);

        return $vote;
    }

    public function getVoteResult(
        TelegramChatEntity $chat,
        TelegramChatUserBanEntity $ban
    ): VoteResult {
        $upVotes = $this->voteRepository->getVotersByType($ban, VoteType::BAN);
        $downVotes = $this->voteRepository->getVotersByType($ban, VoteType::FORGIVE);

        $upVoters = array_values(array_filter(
            array_map(static fn($vote) => $vote->user, $upVotes),
            static fn($user) => $user !== null
        ));
        $downVoters = array_values(array_filter(
            array_map(static fn($vote) => $vote->user, $downVotes),
            static fn($user) => $user !== null
        ));

        $requiredVotes = $this->chatConfigService->getVotesRequired($chat);

        return new VoteResult(
            upVotes: $upVoters,
            downVotes: $downVoters,
            upCount: count($upVoters),
            downCount: count($downVoters),
            requiredVotes: $requiredVotes,
            shouldBan: count($upVoters) >= $requiredVotes,
            shouldForgive: count($downVoters) >= $requiredVotes,
        );
    }
}
