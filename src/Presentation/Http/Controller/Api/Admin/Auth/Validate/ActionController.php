<?php

namespace App\Presentation\Http\Controller\Api\Admin\Auth\Validate;

use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Repository\UserRepository;
use App\Presentation\Http\Controller\Api\Admin\AbstractAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ActionController extends AbstractAdminController
{
    public function __construct(
        protected UserRepository $userRepository,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService);
    }

    public function __invoke(
        Request $request,
        #[CurrentUser]
        AdminSessionEntity $session,
    ): JsonResponse {
        $userName = null;
        $adminChats = $this->userRepository->findByUserIdAdminChats($session->userId);
        if (!empty($adminChats)) {
            $userName = $adminChats[0]->userName ?? $adminChats[0]->username;
        }

        $response = $this->json(
            new ResponseDto(
                valid: true,
                userId: $session->userId,
                userName: $userName,
                expiresAt: $session->expiresAt,
            ),
            Response::HTTP_OK
        );

        $this->refreshSessionCookie($request, $response);

        return $response;
    }
}
