<?php

declare(strict_types=1);

namespace App\Domain\Admin\Entity;

use App\Domain\Admin\Repository\AdminSessionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

/**
 * Admin panel session for access control.
 * Token generated and sent to user in Telegram link.
 * Valid for 1 hour.
 */
#[Entity(repositoryClass: AdminSessionRepository::class)]
#[Table(name: 'admin_sessions')]
#[Index(columns: ['user_id'], name: 'idx_admin_sessions_user_id')]
#[Index(columns: ['expires_at'], name: 'idx_admin_sessions_expires_at')]
class AdminSessionEntity
{
    #[Id]
    #[Column(type: Types::STRING, length: 36)]
    public string $id;

    #[Column(name: 'user_id', type: Types::BIGINT)]
    public int $userId;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $createdAt;

    #[Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $expiresAt;

    public function __construct(
        string $id,
        int $userId,
        int $expiresInSeconds = 3600
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->createdAt = new DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify("+$expiresInSeconds seconds");
    }

    public function isValid(): bool
    {
        return new DateTimeImmutable() < $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return !$this->isValid();
    }

    public function refreshExpiry(int $expiresInSeconds = 3600): void
    {
        $this->expiresAt = (new DateTimeImmutable())->modify("+$expiresInSeconds seconds");
    }
}
