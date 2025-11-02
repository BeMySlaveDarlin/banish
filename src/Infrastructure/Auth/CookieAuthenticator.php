<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Admin\Repository\AdminSessionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class CookieAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly AdminSessionRepository $sessionRepository,
    ) {
    }

    /**
     * @param Request $request
     *
     * @return bool|null
     */
    public function supports(Request $request): ?bool
    {
        return $request->cookies->has('token');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->getCredentials($request);

        if ($token === '') {
            throw new CustomUserMessageAuthenticationException('No token provided');
        }

        return new Passport(
            new UserBadge($token, function ($token) {
                return $this->sessionRepository->find($token);
            }),
            new CustomCredentials(
                function ($credentials, $session) {
                    return $session->id === $credentials;
                },
                $token,
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $responseBody = [
            'error' => \strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($responseBody, Response::HTTP_UNAUTHORIZED);
    }

    private function getCredentials(Request $request): string
    {
        return $request->cookies->get('token', '');
    }
}
