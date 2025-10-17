<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\ValueObject\ResponseMessages;

class BanMessageFormatter
{
    public function formatStartBanMessage(
        TelegramChatUserEntity $reporter,
        TelegramChatUserEntity $spammer
    ): string {
        return sprintf(
            ResponseMessages::START_BAN_PATTERN,
            $reporter->getAlias(),
            $spammer->getAlias()
        );
    }

    public function formatInitialVoteMessage(
        TelegramChatUserEntity $reporter,
        VoteType $voteType
    ): string {
        $emoji = $voteType === VoteType::BAN
            ? ResponseMessages::EMOJI_BAN
            : ResponseMessages::EMOJI_FORGIVE;

        return sprintf(
            ResponseMessages::VOTE_BAN_PATTERN,
            $reporter->getAlias(),
            $voteType->value . ' ' . $emoji
        );
    }

    public function formatVoteMessage(
        TelegramChatUserBanEntity $ban,
        ?TelegramChatUserEntity $reporter,
        ?TelegramChatUserEntity $spammer,
        array $upVoters,
        array $downVoters
    ): string {
        $texts = [
            sprintf(
                ResponseMessages::START_BAN_PATTERN,
                $reporter?->getAlias() ?? 'Unknown',
                $spammer?->getAlias() ?? 'Unknown'
            ),
        ];

        if (!empty($upVoters)) {
            $texts[] = $this->formatVotersLine(
                VoteType::BAN->value . ' ' . ResponseMessages::EMOJI_BAN,
                $upVoters
            );
        }

        if (!empty($downVoters)) {
            $texts[] = $this->formatVotersLine(
                VoteType::FORGIVE->value . ' ' . ResponseMessages::EMOJI_FORGIVE,
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
            ? ResponseMessages::VOTE_BAN_BUTTON_PATTERN
            : ResponseMessages::VOTE_FORGIVE_BUTTON_PATTERN;

        return sprintf($pattern, $currentVotes, $requiredVotes);
    }

    private function formatVotersLine(string $voteType, array $voters): string
    {
        $votersText = implode(' ', $voters);

        return sprintf(ResponseMessages::VOTE_BAN_PATTERN, $votersText, $voteType);
    }
}
