<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin;

use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChatsController extends AbstractAdminController
{
    public function __construct(
        protected BanRepository $banRepository,
        UserRepository $userRepository,
        ChatRepository $chatRepository,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService, $userRepository, $chatRepository);
    }

    public function listAction(
        Request $request,
        #[CurrentUser]
        AdminSessionEntity $session,
    ): JsonResponse {
        $adminChats = $this->userRepository->findByUserIdAdminChats($session->userId);
        if (empty($adminChats)) {
            $response = $this->json(['chats' => [], 'total' => 0]);
            $this->refreshSessionCookie($request, $response);

            return $response;
        }

        $chatIds = array_map(static fn(TelegramChatUserEntity $cu) => $cu->chatId, $adminChats);
        $chats = $this->chatRepository->findBy(['chatId' => $chatIds]);

        $banStats = $this->banRepository->countByChatsBatch($chatIds);
        $memberCounts = $this->userRepository->countByChatsBatch($chatIds);

        $chatsData = array_map(static function (TelegramChatEntity $chat) use ($banStats, $memberCounts) {
            $chatBanStats = $banStats[$chat->chatId] ?? ['totalBans' => 0, 'activeBans' => 0];

            return [
                'id' => $chat->chatId,
                'title' => $chat->name,
                'membersCount' => $memberCounts[$chat->chatId] ?? 0,
                'isEnabled' => $chat->isEnabled,
                'stats' => [
                    'totalBans' => $chatBanStats['totalBans'],
                    'activeBans' => $chatBanStats['activeBans'],
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
