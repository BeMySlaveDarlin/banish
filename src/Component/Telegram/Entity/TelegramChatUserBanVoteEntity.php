<?php

namespace App\Component\Telegram\Entity;

use App\Component\Platform\Entity\PlatformInstanceEntity;
use App\Component\Telegram\Repository\TelegramChatUserBanVoteRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: TelegramChatUserBanVoteRepository::class)]
#[Table(name: '`telegram_chats_users_bans_votes`')]
#[Index(columns: ['ban_id'], name: 'idx_telegram_chats_users_bans_votes_ban_id')]
#[Index(columns: ['chat_id'], name: 'idx_telegram_chats_users_bans_votes_chat_id')]
#[Index(columns: ['user_id'], name: 'idx_telegram_chats_users_bans_votes_user_id')]
#[Index(columns: ['vote'], name: 'idx_telegram_chats_users_bans_votes_vote')]
#[Index(columns: ['created_at'], name: 'idx_telegram_chats_users_bans_votes_created_at')]
#[HasLifecycleCallbacks]
class TelegramChatUserBanVoteEntity
{
    public const string TYPE_DO_BAN = 'ban';
    public const string TYPE_FORGIVE = 'forgive';

    #[Id]
    #[GeneratedValue(strategy: "SEQUENCE")]
    #[SequenceGenerator(sequenceName: "telegram_chats_users_bans_votes_id_seq", allocationSize: 1, initialValue: 1)]
    #[Column(type: Types::BIGINT)]
    public string $id;

    #[Column(name: 'ban_id', type: Types::BIGINT, length: 255)]
    public string $banId;

    #[Column(name: 'chat_id', type: Types::BIGINT, length: 255)]
    public string $chatId;

    #[Column(name: 'user_id', type: Types::BIGINT, length: 255)]
    public string $userId;

    #[Column(type: Types::STRING, options: ['default' => self::TYPE_DO_BAN])]
    public string $vote;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    public DateTimeImmutable $createdAt;
    #[Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    public DateTimeImmutable $updatedAt;

    #[ManyToOne(targetEntity: TelegramChatUserBanEntity::class)]
    public TelegramChatUserBanEntity $ban;

    #[OneToOne(targetEntity: TelegramChatUserEntity::class)]
    public ?TelegramChatUserEntity $user = null;

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
