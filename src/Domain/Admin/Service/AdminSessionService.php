<?php

declare(strict_types=1);

namespace App\Domain\Admin\Service;

use App\Domain\Admin\Entity\AdminExchangeTokenEntity;
use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Domain\Admin\Repository\AdminExchangeTokenRepository;
use App\Domain\Admin\Repository\AdminSessionRepository;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

final readonly class AdminSessionService
{
    private const int EXCHANGE_TOKEN_TTL_SECONDS = 300;

    public function __construct(
        private AdminSessionRepository $sessionRepository,
        private AdminExchangeTokenRepository $exchangeTokenRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $token
     * @param int $userId
     * @param int $expiresInSeconds
     *
     * @return AdminSessionEntity
     */
    public function createSession(
        string $token,
        int $userId,
        int $expiresInSeconds = 3600
    ): AdminSessionEntity {
        $session = new AdminSessionEntity($token, $userId, $expiresInSeconds);
        $this->sessionRepository->save($session);

        $this->logger->info('Admin session created', [
            'userId' => $userId,
            'expiresAt' => $session->expiresAt->format('Y-m-d H:i:s'),
        ]);

        return $session;
    }

    public function validateSession(string $token): ?AdminSessionEntity
    {
        return $this->sessionRepository->findValidSession($token);
    }

    public function revokeSession(string $token): bool
    {
        $session = $this->sessionRepository->find($token);

        if (!$session) {
            return false;
        }

        $this->sessionRepository->remove($session);

        $this->logger->info('Admin session revoked', [
            'userId' => $session->userId,
        ]);

        return true;
    }

    /**
     * @param int $userId
     *
     * @return AdminSessionEntity[]
     */
    public function getActiveSessions(int $userId): array
    {
        return $this->sessionRepository->findActiveByUser($userId);
    }

    public function cleanupExpiredSessions(): int
    {
        return $this->sessionRepository->cleanupExpiredSessions();
    }

    public function getOrCreateSession(
        int $userId,
        int $expiresInSeconds = 3600
    ): AdminSessionEntity {
        $activeSessions = $this->getActiveSessions($userId);

        if (!empty($activeSessions)) {
            $session = $activeSessions[0];
            $session->refreshExpiry($expiresInSeconds);
            $this->sessionRepository->save($session);

            $this->logger->info('Admin session refreshed', [
                'userId' => $userId,
                'expiresAt' => $session->expiresAt->format('Y-m-d H:i:s'),
            ]);

            return $session;
        }

        $token = $this->generateToken();

        return $this->createSession($token, $userId, $expiresInSeconds);
    }

    public function updateSession(AdminSessionEntity $session): void
    {
        $this->sessionRepository->save($session);

        $this->logger->info('Admin session updated', [
            'userId' => $session->userId,
            'expiresAt' => $session->expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function createExchangeToken(int $userId, string $sessionId): AdminExchangeTokenEntity
    {
        $token = new AdminExchangeTokenEntity(
            $this->generateToken(),
            $userId,
            $sessionId,
            self::EXCHANGE_TOKEN_TTL_SECONDS
        );
        $this->exchangeTokenRepository->save($token);

        $this->logger->info('Exchange token created', [
            'userId' => $userId,
            'expiresAt' => $token->expiresAt->format('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function exchangeToken(string $tokenId): ?AdminSessionEntity
    {
        $token = $this->exchangeTokenRepository->findValidToken($tokenId);

        if ($token === null) {
            return null;
        }

        $token->markUsed();
        $this->exchangeTokenRepository->save($token);

        $session = $this->validateSession($token->sessionId);

        if ($session === null) {
            return null;
        }

        $this->logger->info('Exchange token used', [
            'userId' => $token->userId,
        ]);

        return $session;
    }

    public function cleanupExpiredTokens(): int
    {
        return $this->exchangeTokenRepository->cleanupExpiredTokens();
    }

    private function generateToken(): string
    {
        return Uuid::uuid4()->toString();
    }
}
