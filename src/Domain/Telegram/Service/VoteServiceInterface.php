<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\ValueObject\VoteResult;

interface VoteServiceInterface
{
    public function vote(
        TelegramChatEntity $chat,
        TelegramChatUserEntity $user,
        TelegramChatUserBanEntity $ban,
        VoteType $voteType
    ): ?TelegramChatUserBanVoteEntity;

    public function getVoteResult(
        TelegramChatEntity $chat,
        TelegramChatUserBanEntity $ban
    ): VoteResult;
}
