<?php

namespace App\Component\Telegram\Entity;

use App\Component\Telegram\Repository\TelegramChatUserBanRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: TelegramChatUserBanRepository::class)]
#[Table(name: '`telegram_chats_users_bans`')]
#[Index(columns: ['chat_id'], name: 'idx_telegram_chats_users_bans_chat_id')]
#[Index(columns: ['ban_message_id'], name: 'idx_telegram_chats_users_bans_ban_message_id')]
#[Index(columns: ['spam_message_id'], name: 'idx_telegram_chats_users_bans_spam_message_id')]
#[Index(columns: ['initial_message_id'], name: 'idx_telegram_chats_users_bans_initial_message_id')]
#[Index(columns: ['spammer_user_id'], name: 'idx_telegram_chats_users_bans_spammer_user_id')]
#[Index(columns: ['reporter_user_id'], name: 'idx_telegram_chats_users_bans_reporter_user_id')]
#[Index(columns: ['status'], name: 'idx_telegram_chats_users_bans_status')]
#[Index(columns: ['created_at'], name: 'idx_telegram_chats_users_bans_created_at')]
#[HasLifecycleCallbacks]
class TelegramChatUserBanEntity
{
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_BANNED = 'banned';
    public const string STATUS_CANCELED = 'canceled';
    public const string STATUS_DELETED = 'deleted';

    #[Id]
    #[GeneratedValue(strategy: "SEQUENCE")]
    #[SequenceGenerator(sequenceName: "telegram_chats_users_bans_id_seq", allocationSize: 1, initialValue: 1)]
    #[Column(type: Types::BIGINT)]
    public string $id;

    #[Column(name: 'chat_id', type: Types::BIGINT, length: 255)]
    public int $chatId;

    #[Column(name: 'ban_message_id', type: Types::BIGINT, length: 255)]
    public int $banMessageId;

    #[Column(name: 'spam_message_id', type: Types::BIGINT, length: 255, nullable: true)]
    public ?int $spamMessageId = null;

    #[Column(name: 'initial_message_id', type: Types::BIGINT, length: 255, nullable: true)]
    public ?int $initialMessageId = null;

    #[Column(name: 'spammer_user_id', type: Types::BIGINT, length: 255)]
    public int $spammerId;

    #[Column(name: 'reporter_user_id', type: Types::BIGINT, length: 255, nullable: true)]
    public int $reporterId;

    #[Column(type: Types::STRING, options: ['default' => self::STATUS_PENDING])]
    public string $status;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    public DateTimeImmutable $createdAt;
    #[Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    public DateTimeImmutable $updatedAt;

    /**
     * @var ArrayCollection | Collection |TelegramChatUserBanVoteEntity[]
     */
    #[OneToMany(mappedBy: 'ban', targetEntity: TelegramChatUserBanVoteEntity::class)]
    public Collection | ArrayCollection | array $votes;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
    }

    #[PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->status = self::STATUS_PENDING;
    }

    #[PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isBanned(): bool
    {
        return $this->status === self::STATUS_BANNED;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }
}
