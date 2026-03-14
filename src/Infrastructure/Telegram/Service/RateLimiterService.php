<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Service;

use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RateLimiterService
{
    public function __construct(
        private RateLimiterFactory $banInitiationLimiter,
        private RateLimiterFactory $votingLimiter
    ) {
    }

    public function allowBanInitiation(int $userId): bool
    {
        $limiter = $this->banInitiationLimiter->create('ban_initiation_' . $userId);

        return $limiter->consume()->isAccepted();
    }

    public function allowVoting(int $userId): bool
    {
        $limiter = $this->votingLimiter->create('voting_' . $userId);

        return $limiter->consume()->isAccepted();
    }
}
