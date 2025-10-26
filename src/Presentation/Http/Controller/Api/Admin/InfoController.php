<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin;

use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository as UserRepo;
use App\Domain\Telegram\Repository\VoteRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class InfoController extends AbstractAdminController
{
    public function __construct(
        protected ChatRepository $chatRepository,
        protected BanRepository $banRepository,
        protected VoteRepository $voteRepository,
        protected UserRepo $userRepository,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService);
    }

    public function getAction(int $chatId, Request $request): JsonResponse
    {
        $chat = $this->getChatWithAccess($chatId, $request);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found or access denied'], 403);
        }

        $limit = (int) ($request->query->get('limit') ?? '5');
        $offset = (int) ($request->query->get('offset') ?? '0');
        $limit = min($limit, 50);
        $offset = max($offset, 0);

        $totalBans = $this->banRepository->countByChat($chatId);
        $activeBans = $this->banRepository->countActiveBans($chatId);
        $totalVotes = $this->voteRepository->countByChat($chatId);
        $totalUsers = $this->userRepository->countByChat($chatId);

        $recentBans = $this->banRepository->findRecentWithPagination($chatId, $limit, $offset);
        $recentBansData = array_map(static function ($ban) {
            return [
                'id' => $ban->id,
                'spammerId' => $ban->spammerId,
                'status' => $ban->status->value,
                'createdAt' => $ban->createdAt->format('Y-m-d H:i:s'),
            ];
        }, $recentBans);

        $response = $this->json([
            'chatId' => $chat->chatId,
            'title' => $chat->name,
            'description' => $chat->name,
            'membersCount' => $this->userRepository->countByChat($chat->chatId),
            'stats' => [
                'totalBans' => $totalBans,
                'activeBans' => $activeBans,
                'totalVotes' => $totalVotes,
                'totalUsers' => $totalUsers,
            ],
            'recentBans' => $recentBansData,
            'totalRecentBans' => $totalBans,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => $offset + $limit < $totalBans,
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }
}
