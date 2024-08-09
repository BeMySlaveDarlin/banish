<?php

namespace App\Component\Telegram\Entity;

use App\Component\Telegram\Repository\TelegramRequestHistoryRepository;
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
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: TelegramRequestHistoryRepository::class)]
#[Table(name: '`telegram_request_history`')]
#[Index(columns: ['chat_id'], name: 'idx_telegram_request_history_chat_id')]
#[Index(columns: ['from_id'], name: 'idx_telegram_request_history_from_id')]
#[Index(columns: ['message_id'], name: 'idx_telegram_request_history_message_id')]
#[Index(columns: ['update_id'], name: 'idx_telegram_request_history_update_id')]
#[Index(columns: ['created_at'], name: 'idx_telegram_request_history_created_at')]
#[HasLifecycleCallbacks]
class TelegramRequestHistoryEntity
{
    #[Id]
    #[GeneratedValue(strategy: "SEQUENCE")]
    #[SequenceGenerator(sequenceName: "telegram_request_history_id_seq", allocationSize: 1, initialValue: 1)]
    #[Column(type: Types::BIGINT)]
    public string $id;

    #[Column(name: 'chat_id', type: Types::BIGINT, length: 255)]
    public int $chatId;

    #[Column(name: 'from_id', type: Types::BIGINT, length: 255)]
    public int $fromId;

    #[Column(name: 'message_id', type: Types::BIGINT, length: 255)]
    public int $messageId;

    #[Column(name: 'update_id', type: Types::BIGINT, length: 255)]
    public int $updateId;

    #[Column(type: JsonBType::NAME, nullable: true)]
    public ?JsonBValue $request = null;

    #[Column(type: JsonBType::NAME, nullable: true)]
    public ?JsonBValue $response = null;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    public DateTimeImmutable $createdAt;

    public bool $isNew = false;

    public function setResponse(mixed $data = null): void
    {
        $this->response = new JsonBValue($data ?? []);
    }

    public function setRequest(mixed $data = null): void
    {
        $this->request = new JsonBValue($data ?? []);
    }

    #[PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }
}
