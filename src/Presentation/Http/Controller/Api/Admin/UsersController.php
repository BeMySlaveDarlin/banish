<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin;

use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Domain\Admin\Enum\AdminActionType;
use App\Domain\Admin\Service\AdminActionLogService;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\Service\TelegramApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UsersController extends AbstractAdminController
{
    public function __construct(
        protected ChatRepository $chatRepository,
        protected UserRepository $userRepository,
        protected BanRepository $banRepository,
        protected VoteRepository $voteRepository,
        protected AdminActionLogService $logService,
        protected TelegramApiService $telegramApiService,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService);
    }

    public function listAction(int $chatId, Request $request): JsonResponse
    {
        $chat = $this->getChatWithAccess($chatId, $request);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found or access denied'], 403);
        }

        $session = $this->getSession($request);
        $this->logService->log(
            $session?->userId ?? 0,
            $chatId,
            AdminActionType::USER_LIST_VIEW,
            description: 'Viewed users list'
        );

        $limit = (int) ($request->query->get('limit') ?? '10');
        $offset = (int) ($request->query->get('offset') ?? '0');
        $limit = min($limit, 100);
        $offset = max($offset, 0);

        $totalCount = $this->userRepository->countByChat($chatId);
        $users = $this->userRepository->findByChatWithPagination($chatId, $limit, $offset);

        $usersData = array_map(function ($user) use ($chatId) {
            $activeBans = $this->banRepository->findActiveBansBySpammer($user->userId, $chatId);

            return [
                'id' => $user->userId,
                'username' => $user->username,
                'name' => $user->name,
                'isAdmin' => $user->isAdmin,
                'isBot' => $user->isBot,
                'messagesCount' => $user->messagesCount,
                'trustedCount' => $user->trustedCount,
                'isBanned' => !empty($activeBans),
                'bansCount' => count($activeBans),
            ];
        }, $users);

        $response = $this->json([
            'users' => $usersData,
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => $offset + $limit < $totalCount,
            'chatTitle' => $chat->name,
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }

    public function detailsAction(int $chatId, int $userId, Request $request): JsonResponse
    {
        $chat = $this->getChatWithAccess($chatId, $request);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found or access denied'], 403);
        }

        $user = $this->userRepository->findByChatAndUser($chatId, $userId);
        if (!$user) {
            return $this->json(['error' => 'User not found in chat'], 404);
        }

        $session = $this->getSession($request);
        $this->logService->log(
            $session?->userId ?? 0,
            $chatId,
            AdminActionType::USER_DETAILS_VIEW,
            ['targetUserId' => $userId],
            'Viewed user details'
        );

        $bans = $this->banRepository->findBy([
            'spammerId' => $userId,
            'chatId' => $chatId,
        ]);

        $activeBans = $this->banRepository->findActiveBansBySpammer($user->userId, $chatId);
        $bansData = array_map(static function ($ban) {
            return [
                'id' => $ban->id,
                'status' => $ban->status->value,
                'createdAt' => $ban->createdAt->format('Y-m-d H:i:s'),
                'votesFor' => $ban->votesFor,
                'votesAgainst' => $ban->votesAgainst,
            ];
        }, $bans);

        $response = $this->json([
            'id' => $user->userId,
            'username' => $user->username,
            'name' => $user->name,
            'isAdmin' => $user->isAdmin,
            'isBot' => $user->isBot,
            'messagesCount' => $user->messagesCount,
            'trustedCount' => $user->trustedCount,
            'bans' => $bansData,
            'bansCount' => count($bansData),
            'isBanned' => count($activeBans),
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }

    public function unbanAction(int $chatId, int $userId, Request $request): JsonResponse
    {
        $chat = $this->getChatWithAccess($chatId, $request);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found or access denied'], 403);
        }

        $user = $this->userRepository->findByChatAndUser($chatId, $userId);
        if (!$user) {
            return $this->json(['error' => 'User not found in chat'], 404);
        }

        $activeBans = $this->banRepository->findActiveBansBySpammer($userId, $chatId);
        if (empty($activeBans)) {
            return $this->json(['error' => 'User is not banned'], 400);
        }

        $session = $this->getSession($request);
        try {
            $this->telegramApiService->unbanChatMember($chatId, $userId);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to unban user in Telegram: ' . $e->getMessage()], 500);
        }

        foreach ($activeBans as $ban) {
            $this->voteRepository->deleteByBan($ban, flush: false);
            $this->banRepository->remove($ban, flush: false);
        }
        $this->voteRepository->flush();
        $this->banRepository->flush();

        $this->logService->log(
            $session?->userId ?? 0,
            $chatId,
            AdminActionType::UNBAN_USER,
            ['targetUserId' => $userId, 'bansCount' => count($activeBans)],
            "Unbanned user @{$user->username}"
        );

        $response = $this->json([
            'success' => true,
            'message' => 'User unbanned',
            'bansRemoved' => count($activeBans),
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }

    private function getSession(Request $request): ?AdminSessionEntity
    {
        $token = $this->getTokenFromRequest($request);
        if (!$token) {
            return null;
        }

        return $this->sessionService->validateSession($token);
    }
}
