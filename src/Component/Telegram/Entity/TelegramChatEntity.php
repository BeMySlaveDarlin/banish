<?php

namespace App\Component\Telegram\Entity;

use App\Component\Telegram\Repository\TelegramChatRepository;
use App\Service\Doctrine\Type\JsonBType;
use App\Service\Doctrine\Type\JsonBValue;
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

#[Entity(repositoryClass: TelegramChatRepository::class)]
#[Table(name: '`telegram_chats`')]
#[Index(columns: ['chat_id'], name: 'idx_telegram_chats_chat_id')]
#[Index(columns: ['type'], name: 'idx_telegram_chats_type')]
#[Index(columns: ['created_at'], name: 'idx_telegram_chats_created_at')]
#[HasLifecycleCallbacks]
class TelegramChatEntity
{
    public const string OPTION_BAN_VOTES_REQUIRED = 'ban_votes_required';
    public const string OPTION_DELETE_MESSAGE = 'delete_message';
    public const int DEFAULT_VOTES_REQUIRED = 3;
    public const bool DEFAULT_DELETE_MESSAGES = true;

    #[Id]
    #[GeneratedValue(strategy: "SEQUENCE")]
    #[SequenceGenerator(sequenceName: "telegram_chats_id_seq", allocationSize: 1, initialValue: 1)]
    #[Column(type: Types::BIGINT)]
    public string $id;

    #[Column(name: 'chat_id', type: Types::BIGINT, length: 255)]
    public int $chatId;

    #[Column(type: Types::STRING, length: 255)]
    public string $type;

    #[Column(type: Types::TEXT)]
    public ?string $name = null;

    #[Column(name: 'is_enabled', type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isEnabled;

    #[Column(type: JsonBType::NAME, nullable: true)]
    public ?JsonBValue $options = null;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    public DateTimeImmutable $createdAt;
    #[Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    public DateTimeImmutable $updatedAt;

    public function getOption(string $option): mixed
    {
        return $this->options->get($option);
    }

    public function setOption(string $option, mixed $value): void
    {
        $this->options->set($option, $value);
    }

    public static function getDefaultOptions(): array
    {
        return [
            self::OPTION_BAN_VOTES_REQUIRED => self::DEFAULT_VOTES_REQUIRED,
            self::OPTION_DELETE_MESSAGE => self::DEFAULT_DELETE_MESSAGES,
        ];
    }

    #[PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();

        $filtered = [];
        foreach (self::getDefaultOptions() as $option => $value) {
            if (isset($options[$option])) {
                $filtered[$option] = $options[$option];
            } else {
                $filtered[$option] = $value;
            }
        }

        $this->options = new JsonBValue($filtered);
    }

    #[PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
