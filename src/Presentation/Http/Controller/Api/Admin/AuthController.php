<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin;

use App\Domain\Admin\Enum\AdminActionType;
use App\Domain\Admin\Service\AdminActionLogService;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends AbstractAdminController
{
    public function __construct(
        protected AdminActionLogService $logService,
        protected UserRepository $userRepository,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService);
    }

    public function loginAction(string $token): Response
    {
        $session = $this->sessionService->validateSession($token);
        if (!$session) {
            return $this->json(['error' => 'Invalid or expired token'], 401);
        }

        $this->logService->log(
            $session->userId,
            0,
            AdminActionType::AUTH_LOGIN,
            description: 'Admin logged in'
        );

        $userName = null;
        $adminChats = $this->userRepository->findByUserIdAdminChats($session->userId);
        if (!empty($adminChats)) {
            $userName = $adminChats[0]->userName ?? $adminChats[0]->username;
        }

        $response = $this->json([
            'success' => true,
            'userId' => $session->userId,
            'userName' => $userName,
            'expiresAt' => $session->expiresAt->format('Y-m-d H:i:s'),
        ]);

        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withValue($token)
            ->withExpires(time() + self::COOKIE_LIFETIME)
            ->withHttpOnly()
            ->withSecure()
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        return $response;
    }

    public function validateAction(Request $request): JsonResponse
    {
        $token = $this->getTokenFromRequest($request);
        if (!$token) {
            return $this->json(['error' => 'No session token'], 401);
        }

        $session = $this->sessionService->validateSession($token);
        if (!$session) {
            return $this->json(['error' => 'Invalid or expired session'], 401);
        }

        $userName = null;
        $adminChats = $this->userRepository->findByUserIdAdminChats($session->userId);
        if (!empty($adminChats)) {
            $userName = $adminChats[0]->userName ?? $adminChats[0]->username;
        }

        $response = $this->json([
            'valid' => true,
            'userId' => $session->userId,
            'userName' => $userName,
            'expiresAt' => $session->expiresAt->format('Y-m-d H:i:s'),
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }

    public function logoutAction(Request $request): Response
    {
        $token = $this->getTokenFromRequest($request);
        $session = null;

        if ($token) {
            $session = $this->sessionService->validateSession($token);
            $this->sessionService->revokeSession($token);
        }

        if ($session) {
            $this->logService->log(
                $session->userId,
                0,
                AdminActionType::AUTH_LOGOUT,
                description: 'Admin logged out'
            );
        }

        $response = $this->json(['success' => true]);
        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withExpires()
            ->withHttpOnly();

        $response->headers->setCookie($cookie);

        return $response;
    }
}
