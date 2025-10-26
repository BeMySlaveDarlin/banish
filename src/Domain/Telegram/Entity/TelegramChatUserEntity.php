<?php

namespace App\Domain\Telegram\Entity;

use App\Domain\Telegram\Enum\UserStatus;
use App\Domain\Telegram\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: UserRepository::class)]
#[Table(name: '`telegram_chats_users`')]
#[Index(columns: ['chat_id'], name: 'idx_telegram_chats_users_chat_id')]
#[Index(columns: ['user_id'], name: 'idx_telegram_chats_users_user_id')]
#[Index(columns: ['is_admin'], name: 'idx_telegram_chats_users_is_admin')]
#[Index(columns: ['is_bot'], name: 'idx_telegram_chats_users_is_bot')]
#[Index(columns: ['created_at'], name: 'idx_telegram_chats_users_created_at')]
#[HasLifecycleCallbacks]
class TelegramChatUserEntity
{
    #[Id]
    #[GeneratedValue(strategy: "SEQUENCE")]
    #[SequenceGenerator(sequenceName: "telegram_chats_users_id_seq", allocationSize: 1, initialValue: 1)]
    #[Column(type: Types::BIGINT)]
    public string $id;

    #[Column(name: 'chat_id', type: Types::BIGINT, length: 255)]
    public int $chatId;

    #[Column(name: 'user_id', type: Types::BIGINT, length: 255)]
    public int $userId;

    #[Column(name: 'username', type: Types::STRING, length: 255, nullable: true)]
    public ?string $username = null;

    #[Column(name: 'name', type: Types::TEXT, nullable: true)]
    public ?string $name = null;

    #[Column(name: 'is_admin', type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isAdmin;

    #[Column(name: 'is_bot', type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isBot;

    #[Column(name: 'status', type: Types::STRING, enumType: UserStatus::class, options: ['default' => 'active'])]
    public UserStatus $status = UserStatus::ACTIVE;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    public DateTimeImmutable $createdAt;
    #[Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    public DateTimeImmutable $updatedAt;

    public function getAlias(): string
    {
        if (null !== $this->username) {
            return '@' . $this->username;
        }

        return $this->name ?? 'User';
    }

    #[PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
