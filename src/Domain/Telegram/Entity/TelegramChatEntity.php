<?php

namespace App\Domain\Telegram\Entity;

use App\Domain\Telegram\Constants\ChatDefaults;
use App\Domain\Telegram\Constants\Emoji;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Infrastructure\Doctrine\Type\JsonBType;
use App\Infrastructure\Doctrine\Type\JsonBValue;
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

#[Entity(repositoryClass: ChatRepository::class)]
#[Table(name: '`telegram_chats`')]
#[Index(columns: ['chat_id'], name: 'idx_telegram_chats_chat_id')]
#[Index(columns: ['type'], name: 'idx_telegram_chats_type')]
#[Index(columns: ['created_at'], name: 'idx_telegram_chats_created_at')]
#[HasLifecycleCallbacks]
class TelegramChatEntity
{
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
        return $this->options?->get($option);
    }

    public function setOption(string $option, mixed $value): void
    {
        $this->options?->set($option, $value);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getDefaultOptions(): array
    {
        return [
            ChatDefaults::OPTION_BAN_VOTES_REQUIRED => ChatDefaults::DEFAULT_VOTES_REQUIRED,
            ChatDefaults::OPTION_DELETE_MESSAGE => ChatDefaults::DEFAULT_DELETE_MESSAGES,
            ChatDefaults::OPTION_DELETE_ONLY => ChatDefaults::DEFAULT_DELETE_ONLY,
            ChatDefaults::OPTION_MIN_MESSAGES_FOR_TRUST => ChatDefaults::DEFAULT_MIN_MESSAGES_FOR_TRUST,
            ChatDefaults::OPTION_BAN_EMOJI => Emoji::DEFAULT_BAN,
            ChatDefaults::OPTION_FORGIVE_EMOJI => Emoji::DEFAULT_FORGIVE,
            ChatDefaults::OPTION_ENABLE_REACTIONS => ChatDefaults::DEFAULT_ENABLE_REACTIONS,
        ];
    }

    #[PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();

        $filtered = [];
        $options = $this->options?->toArray() ?? [];
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
