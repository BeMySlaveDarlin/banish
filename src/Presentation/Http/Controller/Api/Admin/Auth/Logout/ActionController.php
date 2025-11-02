<?php

namespace App\Presentation\Http\Controller\Api\Admin\Auth\Logout;

use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Domain\Admin\Enum\AdminActionType;
use App\Domain\Admin\Service\AdminActionLogService;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Repository\UserRepository;
use App\Presentation\Http\Controller\Api\Admin\AbstractAdminController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ActionController extends AbstractAdminController
{
    public function __construct(
        protected AdminActionLogService $logService,
        protected UserRepository $userRepository,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService);
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

        $response = $this->json(new ResponseDto(success: true), Response::HTTP_OK);
        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withExpires()
            ->withHttpOnly();

        $response->headers->setCookie($cookie);

        return $response;
    }
}
