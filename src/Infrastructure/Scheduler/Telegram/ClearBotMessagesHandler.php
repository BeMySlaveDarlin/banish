<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler\Telegram;

use App\Domain\Telegram\Service\BanMessageCleanupService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ClearBotMessagesHandler
{
    public function __construct(
        private readonly BanMessageCleanupService $cleanupService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ClearBotMessagesMessage $message): void
    {
        try {
            $this->cleanupService->clearBotMessages();
            $this->logger->info('Clear bot messages task completed successfully');
        } catch (\Throwable $e) {
            $this->logger->error('Clear bot messages task failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
