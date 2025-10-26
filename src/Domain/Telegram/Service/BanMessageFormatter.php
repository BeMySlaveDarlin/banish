<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Constants\Emoji;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;

class BanMessageFormatter
{
    public function formatStartBanMessage(
        TelegramChatUserEntity $reporter,
        TelegramChatUserEntity $spammer
    ): string {
        return sprintf(
            Messages::START_BAN_PATTERN,
            $reporter->getAlias(),
            $spammer->getAlias()
        );
    }

    public function formatInitialVoteMessage(
        TelegramChatUserEntity $reporter,
        VoteType $voteType
    ): string {
        $emoji = $voteType === VoteType::BAN
            ? Emoji::BAN
            : Emoji::FORGIVE;

        return sprintf(
            Messages::VOTE_BAN_PATTERN,
            $reporter->getAlias(),
            $voteType->value . ' ' . $emoji
        );
    }

    /**
     * @param array<int, TelegramChatUserEntity> $upVoters
     * @param array<int, TelegramChatUserEntity> $downVoters
     */
    public function formatVoteMessage(
        TelegramChatUserBanEntity $ban,
        ?TelegramChatUserEntity $reporter,
        ?TelegramChatUserEntity $spammer,
        array $upVoters,
        array $downVoters
    ): string {
        $texts = [
            sprintf(
                Messages::START_BAN_PATTERN,
                $reporter?->getAlias() ?? 'Unknown',
                $spammer?->getAlias() ?? 'Unknown'
            ),
        ];

        if (!empty($upVoters)) {
            $texts[] = $this->formatVotersLine(
                VoteType::BAN->value . ' ' . Emoji::BAN,
                $upVoters
            );
        }

        if (!empty($downVoters)) {
            $texts[] = $this->formatVotersLine(
                VoteType::FORGIVE->value . ' ' . Emoji::FORGIVE,
                $downVoters
            );
        }

        if (!$ban->isPending()) {
            $status = $ban->isBanned() ? 'banned' : 'not banned';
            $texts[] = sprintf("%s is %s", $spammer?->getAlias() ?? 'User', $status);
        }

        return implode("\n", $texts);
    }

    public function formatVoteButtonText(int $currentVotes, int $requiredVotes, VoteType $type): string
    {
        $pattern = $type === VoteType::BAN
            ? Messages::VOTE_BAN_BUTTON_PATTERN
            : Messages::VOTE_FORGIVE_BUTTON_PATTERN;

        return sprintf($pattern, $currentVotes, $requiredVotes);
    }

    /**
     * @param array<int, TelegramChatUserEntity> $voters
     */
    private function formatVotersLine(string $voteType, array $voters): string
    {
        /** @var array<int, string> $votersAliases */
        $votersAliases = array_map(static fn($voter) => $voter->getAlias(), $voters);
        $votersText = implode(' ', $votersAliases);

        return sprintf(Messages::VOTE_BAN_PATTERN, $votersText, $voteType);
    }
}
