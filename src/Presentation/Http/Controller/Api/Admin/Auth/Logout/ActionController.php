<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin\Auth\Logout;

use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Domain\Admin\Enum\AdminActionType;
use App\Domain\Admin\Service\AdminActionLogService;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Presentation\Http\Controller\Api\Admin\AbstractAdminController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ActionController extends AbstractAdminController
{
    public function __construct(
        protected AdminActionLogService $logService,
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
    ): Response {
        $this->logService->log(
            $session->userId,
            0,
            AdminActionType::AUTH_LOGOUT,
            description: 'Admin logged out'
        );

        $this->sessionService->revokeSession($session->id);

        $response = $this->json(new ResponseDto(success: true), Response::HTTP_OK);
        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withExpires()
            ->withHttpOnly();

        $response->headers->setCookie($cookie);

        return $response;
    }
}
