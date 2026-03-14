<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler\Admin;

use App\Domain\Admin\Service\AdminSessionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CleanupAdminSessionsHandler
{
    public function __construct(
        private readonly AdminSessionService $sessionService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CleanupAdminSessionsMessage $message): void
    {
        try {
            $deletedSessions = $this->sessionService->cleanupExpiredSessions();
            $deletedTokens = $this->sessionService->cleanupExpiredTokens();

            $this->logger->info('Admin sessions cleanup completed', [
                'deletedSessions' => $deletedSessions,
                'deletedTokens' => $deletedTokens,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Admin sessions cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
