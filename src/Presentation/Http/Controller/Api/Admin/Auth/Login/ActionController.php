<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin\Auth\Login;

use App\Domain\Admin\Enum\AdminActionType;
use App\Domain\Admin\Service\AdminActionLogService;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class ActionController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload]
        RequestDto $dto,
        Request $request,
        AdminSessionService $sessionService,
        AdminActionLogService $logService,
        UserRepository $userRepository,
        RateLimiterFactory $adminLoginLimiter,
    ): Response {
        $limiter = $adminLoginLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume()->isAccepted()) {
            return $this->json(
                ['error' => 'Too many login attempts, try again later'],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        $session = $sessionService->exchangeToken($dto->token);

        if (!$session) {
            return $this->json(['error' => 'Invalid or expired token'], 401);
        }

        $logService->log(
            $session->userId,
            0,
            AdminActionType::AUTH_LOGIN,
            description: 'Admin logged in'
        );

        $userName = null;
        $adminChats = $userRepository->findByUserIdAdminChats($session->userId);
        if (!empty($adminChats)) {
            $userName = $adminChats[0]->username;
        }

        $response = $this->json(new ResponseDto(
            success: true,
            userId: $session->userId,
            userName: $userName,
            expiresAt: $session->expiresAt,
        ), Response::HTTP_OK);

        $cookie = Cookie::create('token')
            ->withValue($session->id)
            ->withExpires(time() + 3600)
            ->withHttpOnly()
            ->withSecure()
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        return $response;
    }
}
