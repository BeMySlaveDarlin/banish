<?php

declare(strict_types=1);

namespace App\Domain\Admin\Entity;

use App\Domain\Admin\Repository\AdminExchangeTokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: AdminExchangeTokenRepository::class)]
#[Table(name: 'admin_exchange_tokens')]
#[Index(columns: ['user_id'], name: 'idx_admin_exchange_tokens_user_id')]
#[Index(columns: ['expires_at'], name: 'idx_admin_exchange_tokens_expires_at')]
class AdminExchangeTokenEntity
{
    private const int DEFAULT_TTL_SECONDS = 300;

    #[Id]
    #[Column(type: Types::STRING, length: 36)]
    public string $id;

    #[Column(name: 'user_id', type: Types::BIGINT)]
    public int $userId;

    #[Column(name: 'session_id', type: Types::STRING, length: 36)]
    public string $sessionId;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $createdAt;

    #[Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $expiresAt;

    #[Column(name: 'used', type: Types::BOOLEAN, options: ['default' => false])]
    public bool $used = false;

    public function __construct(
        string $id,
        int $userId,
        string $sessionId,
        int $ttlSeconds = self::DEFAULT_TTL_SECONDS
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->createdAt = new DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify("+$ttlSeconds seconds");
    }

    public function isValid(): bool
    {
        return !$this->used && new DateTimeImmutable() < $this->expiresAt;
    }

    public function markUsed(): void
    {
        $this->used = true;
    }
}
