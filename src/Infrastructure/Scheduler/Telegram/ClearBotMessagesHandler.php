<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler\Telegram;

use App\Domain\Telegram\Service\BanMessageCleanupService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ClearBotMessagesHandler
{
    public function __construct(
        private BanMessageCleanupService $cleanupService
    ) {
    }

    public function __invoke(ClearBotMessagesMessage $message): void
    {
        $this->cleanupService->clearBotMessages();
    }
}
