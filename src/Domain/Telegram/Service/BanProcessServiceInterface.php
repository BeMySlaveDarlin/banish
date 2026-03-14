<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\ValueObject\VoteResult;

interface BanProcessServiceInterface
{
    public function initiateBan(
        TelegramChatEntity $chat,
        TelegramChatUserEntity $reporter,
        int $spammerId,
        int $banMessageId,
        ?int $spamMessageId = null,
        ?int $initialMessageId = null
    ): TelegramChatUserBanEntity;

    public function processVote(
        TelegramChatEntity $chat,
        TelegramChatUserEntity $user,
        TelegramChatUserBanEntity $ban,
        VoteType $voteType
    ): VoteResult;

    public function checkAndExecuteVerdict(
        TelegramChatEntity $chat,
        TelegramChatUserBanEntity $ban
    ): VoteResult;
}
