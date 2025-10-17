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
        private VoteRepository $voteRepository,
        private ChatConfigService $chatConfigService
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

    public function getVoteResult(
        TelegramChatEntity $chat,
        TelegramChatUserBanEntity $ban
    ): array {
        $upVotes = $this->voteRepository->getVotersByType($ban, VoteType::BAN);
        $downVotes = $this->voteRepository->getVotersByType($ban, VoteType::FORGIVE);

        $upVotersAliases = array_map(fn($vote) => $vote->user->getAlias(), $upVotes);
        $downVotersAliases = array_map(fn($vote) => $vote->user->getAlias(), $downVotes);

        $requiredVotes = $this->chatConfigService->getVotesRequired($chat);

        return [
            'upVotes' => $upVotersAliases,
            'downVotes' => $downVotersAliases,
            'upCount' => count($upVotersAliases),
            'downCount' => count($downVotersAliases),
            'requiredVotes' => $requiredVotes,
            'shouldBan' => count($upVotersAliases) >= $requiredVotes,
            'shouldForgive' => count($downVotersAliases) >= $requiredVotes,
        ];
    }
}
