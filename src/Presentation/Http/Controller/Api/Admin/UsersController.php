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
use App\Domain\Telegram\Service\BanServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class UsersController extends AbstractAdminController
{
    public function __construct(
        protected BanRepository $banRepository,
        protected VoteRepository $voteRepository,
        protected AdminActionLogService $logService,
        protected BanServiceInterface $banService,
        UserRepository $userRepository,
        ChatRepository $chatRepository,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService, $userRepository, $chatRepository);
    }

    public function listAction(
        int $chatId,
        Request $request,
        #[CurrentUser]
        AdminSessionEntity $session,
    ): JsonResponse {
        $chat = $this->getChatWithAccess($chatId, $request);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found or access denied'], 403);
        }

        $this->logService->log(
            $session->userId,
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

        $userIds = array_map(static fn($user) => $user->userId, $users);
        $activeBansCounts = $this->banRepository->countActiveBansByUsersBatch($userIds, $chatId);

        $usersData = array_map(static function ($user) use ($activeBansCounts) {
            $bansCount = $activeBansCounts[$user->userId] ?? 0;

            return [
                'id' => $user->userId,
                'username' => $user->username,
                'name' => $user->name,
                'isAdmin' => $user->isAdmin,
                'isBot' => $user->isBot,
                'isBanned' => $bansCount > 0,
                'bansCount' => $bansCount,
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

    public function detailsAction(
        int $chatId,
        int $userId,
        Request $request,
        #[CurrentUser]
        AdminSessionEntity $session,
    ): JsonResponse {
        $chat = $this->getChatWithAccess($chatId, $request);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found or access denied'], 403);
        }

        $user = $this->userRepository->findByChatAndUser($chatId, $userId);
        if (!$user) {
            return $this->json(['error' => 'User not found in chat'], 404);
        }

        $this->logService->log(
            $session->userId,
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
        $bansData = array_map(function ($ban) {
            return [
                'id' => $ban->id,
                'status' => $ban->getStatus()->value,
                'createdAt' => $ban->createdAt->format('Y-m-d H:i:s'),
                'votesFor' => $this->voteRepository->countVotesByType($ban, \App\Domain\Telegram\Enum\VoteType::BAN),
                'votesAgainst' => $this->voteRepository->countVotesByType($ban, \App\Domain\Telegram\Enum\VoteType::FORGIVE),
            ];
        }, $bans);

        $response = $this->json([
            'id' => $user->userId,
            'username' => $user->username,
            'name' => $user->name,
            'isAdmin' => $user->isAdmin,
            'isBot' => $user->isBot,
            'bans' => $bansData,
            'bansCount' => count($bansData),
            'isBanned' => count($activeBans) > 0,
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }

    public function unbanAction(
        int $chatId,
        int $userId,
        Request $request,
        #[CurrentUser]
        AdminSessionEntity $session,
    ): JsonResponse {
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

        try {
            $this->banService->adminUnban($chatId, $userId, $activeBans);
        } catch (\Throwable) {
            return $this->json(['error' => 'Failed to unban user in Telegram'], 500);
        }

        $this->logService->log(
            $session->userId,
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
}
