<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin;

use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Domain\Admin\Service\AdminActionLogService;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuditLogController extends AbstractAdminController
{
    public function __construct(
        protected AdminActionLogService $logService,
        protected UserRepository $userRepository,
        protected ChatRepository $chatRepository,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService);
    }

    public function chatLogsAction(
        int $chatId,
        Request $request,
        #[CurrentUser]
        AdminSessionEntity $session,
    ): JsonResponse {
        $chatUser = $this->userRepository->findByChatAndUser($chatId, $session->userId);
        if (!$chatUser || !$chatUser->isAdmin) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $limit = (int) ($request->query->get('limit') ?? '10');
        $limit = min($limit, 100);

        $logs = $this->logService->getChatLogs($chatId, $limit);

        $logsData = array_map(static function ($log) {
            return [
                'id' => $log->id,
                'userId' => $log->userId,
                'actionType' => $log->actionType->value,
                'description' => $log->description,
                'data' => $log->data,
                'createdAt' => $log->createdAt->format('Y-m-d H:i:s'),
            ];
        }, $logs);

        $chat = $this->chatRepository->findByChatId($chatId);
        $chatTitle = $chat?->name ?? '';

        $response = $this->json([
            'logs' => $logsData,
            'total' => count($logsData),
            'chatTitle' => $chatTitle,
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }

    public function userLogsAction(int $userId, Request $request): JsonResponse
    {
        $limit = (int) ($request->query->get('limit') ?? '50');
        $limit = min($limit, 500);

        $logs = $this->logService->getUserLogs($userId, $limit);

        $logsData = array_map(static function ($log) {
            return [
                'id' => $log->id,
                'chatId' => $log->chatId,
                'actionType' => $log->actionType->value,
                'description' => $log->description,
                'data' => $log->data,
                'createdAt' => $log->createdAt->format('Y-m-d H:i:s'),
            ];
        }, $logs);

        $response = $this->json([
            'logs' => $logsData,
            'total' => count($logsData),
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }
}
