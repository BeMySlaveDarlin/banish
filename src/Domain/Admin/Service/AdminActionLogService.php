<?php

declare(strict_types=1);

namespace App\Domain\Admin\Service;

use App\Domain\Admin\Entity\AdminActionLogEntity;
use App\Domain\Admin\Enum\AdminActionType;
use App\Domain\Admin\Repository\AdminActionLogRepository;
use Psr\Log\LoggerInterface;

final class AdminActionLogService
{
    public function __construct(
        private readonly AdminActionLogRepository $logRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function log(
        int $userId,
        int $chatId,
        AdminActionType $actionType,
        array $data = [],
        ?string $description = null
    ): AdminActionLogEntity {
        $log = new AdminActionLogEntity();
        $log->userId = $userId;
        $log->chatId = $chatId;
        $log->actionType = $actionType;
        $log->data = $data;
        $log->description = $description;

        $this->logRepository->save($log);

        $this->logger->info('Admin action logged', [
            'userId' => $userId,
            'chatId' => $chatId,
            'action' => $actionType->value,
            'description' => $description,
        ]);

        return $log;
    }

    /**
     * @return AdminActionLogEntity[]
     */
    public function getChatLogs(int $chatId, int $limit = 50): array
    {
        return $this->logRepository->findByChat($chatId, $limit);
    }

    /**
     * @return AdminActionLogEntity[]
     */
    public function getUserLogs(int $userId, int $limit = 50): array
    {
        return $this->logRepository->findByUser($userId, $limit);
    }

    /**
     * @return AdminActionLogEntity[]
     */
    public function getChatUserLogs(int $chatId, int $userId, int $limit = 50): array
    {
        return $this->logRepository->findByChatAndUser($chatId, $userId, $limit);
    }
}
