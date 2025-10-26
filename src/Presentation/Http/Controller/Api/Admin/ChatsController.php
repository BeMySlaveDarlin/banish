<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin;

use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ChatsController extends AbstractAdminController
{
    public function __construct(
        protected ChatRepository $chatRepository,
        protected BanRepository $banRepository,
        protected UserRepository $userRepository,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService);
    }

    public function listAction(Request $request): JsonResponse
    {
        $token = $this->getTokenFromRequest($request);
        if (!$token) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $session = $this->sessionService->validateSession($token);
        if (!$session) {
            return $this->json(['error' => 'Invalid or expired session'], 401);
        }

        $adminChats = $this->userRepository->findByUserIdAdminChats($session->userId);
        if (empty($adminChats)) {
            $response = $this->json(['chats' => [], 'total' => 0]);
            $this->refreshSessionCookie($request, $response);

            return $response;
        }

        $chatIds = array_map(static fn(TelegramChatUserEntity $cu) => $cu->chatId, $adminChats);
        $chats = $this->chatRepository->findBy(['chatId' => $chatIds]);

        $chatsData = array_map(function (TelegramChatEntity $chat) {
            $banCount = $this->banRepository->countByChat($chat->chatId);
            $activeBans = $this->banRepository->countActiveBans($chat->chatId);

            return [
                'id' => $chat->chatId,
                'title' => $chat->name,
                'membersCount' => $this->userRepository->countByChat($chat->chatId),
                'isEnabled' => $chat->isEnabled,
                'stats' => [
                    'totalBans' => $banCount,
                    'activeBans' => $activeBans,
                ],
            ];
        }, $chats);

        $response = $this->json([
            'chats' => $chatsData,
            'total' => count($chatsData),
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }
}
