<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin;

use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Entity\TelegramChatEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAdminController extends AbstractController
{
    protected const string COOKIE_NAME = 'token';
    protected const int COOKIE_LIFETIME = 3600;

    public function __construct(
        protected AdminSessionService $sessionService,
    ) {
    }

    protected function refreshSessionCookie(Request $request, Response $response): void
    {
        $token = $request->cookies->get(self::COOKIE_NAME);
        if (!$token) {
            return;
        }

        $session = $this->sessionService->validateSession($token);
        if (!$session) {
            return;
        }

        $session->refreshExpiry(self::COOKIE_LIFETIME);
        $this->sessionService->updateSession($session);

        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withValue($token)
            ->withExpires(time() + self::COOKIE_LIFETIME)
            ->withHttpOnly()
            ->withSecure()
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);
    }

    protected function getTokenFromRequest(Request $request): ?string
    {
        return $request->cookies->get(self::COOKIE_NAME);
    }

    protected function getChatWithAccess(int $chatId, Request $request): ?TelegramChatEntity
    {
        $token = $this->getTokenFromRequest($request);
        if (!$token) {
            return null;
        }

        $session = $this->sessionService->validateSession($token);
        if (!$session) {
            return null;
        }
        if (property_exists($this, 'userRepository') && property_exists($this, 'chatRepository')) {
            $chatUser = $this->userRepository->findByChatAndUser($chatId, $session->userId);
            if (!$chatUser || !$chatUser->isAdmin) {
                return null;
            }

            return $this->chatRepository->findByChatId($chatId);
        }

        return null;
    }
}
