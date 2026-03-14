<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin\Auth\Validate;

use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Presentation\Http\Controller\Api\Admin\AbstractAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ActionController extends AbstractAdminController
{
    public function __construct(
        UserRepository $userRepository,
        ChatRepository $chatRepository,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService, $userRepository, $chatRepository);
    }

    public function __invoke(
        Request $request,
        #[CurrentUser]
        AdminSessionEntity $session,
    ): JsonResponse {
        $userName = null;
        $adminChats = $this->userRepository->findByUserIdAdminChats($session->userId);
        if (!empty($adminChats)) {
            $userName = $adminChats[0]->username;
        }

        $response = $this->json(
            new ResponseDto(
                valid: true,
                userId: $session->userId,
                userName: $userName,
                expiresAt: $session->expiresAt,
            ),
        );

        $this->refreshSessionCookie($request, $response);

        return $response;
    }
}
