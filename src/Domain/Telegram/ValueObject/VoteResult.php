<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

use App\Domain\Telegram\Entity\TelegramChatUserEntity;

final readonly class VoteResult
{
    /**
     * @param array<int, TelegramChatUserEntity> $upVotes
     * @param array<int, TelegramChatUserEntity> $downVotes
     */
    public function __construct(
        public array $upVotes,
        public array $downVotes,
        public int $upCount,
        public int $downCount,
        public int $requiredVotes,
        public bool $shouldBan,
        public bool $shouldForgive,
    ) {
    }
}
