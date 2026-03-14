<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;

interface BanMessageFormatterInterface
{
    public function formatStartBanMessage(
        TelegramChatUserEntity $reporter,
        TelegramChatUserEntity $spammer
    ): string;

    public function formatInitialVoteMessage(
        TelegramChatUserEntity $reporter,
        VoteType $voteType
    ): string;

    /**
     * @param array<int, TelegramChatUserEntity> $upVoters
     * @param array<int, TelegramChatUserEntity> $downVoters
     */
    public function formatVoteMessage(
        TelegramChatUserBanEntity $ban,
        ?TelegramChatUserEntity $reporter,
        ?TelegramChatUserEntity $spammer,
        array $upVoters,
        array $downVoters,
        bool $deleteOnlyMessage = false
    ): string;

    public function formatVoteButtonText(int $currentVotes, int $requiredVotes, VoteType $type): string;
}
