<?php

declare(strict_types=1);

namespace App\Domain\Admin\Entity;

use App\Domain\Admin\Enum\AdminActionType;
use App\Domain\Admin\Repository\AdminActionLogRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: AdminActionLogRepository::class)]
#[Table(name: 'admin_action_logs')]
#[Index(columns: ['user_id'], name: 'idx_admin_action_logs_user_id')]
#[Index(columns: ['chat_id'], name: 'idx_admin_action_logs_chat_id')]
#[Index(columns: ['action_type'], name: 'idx_admin_action_logs_action_type')]
#[Index(columns: ['created_at'], name: 'idx_admin_action_logs_created_at')]
class AdminActionLogEntity
{
    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[Column(name: 'user_id', type: Types::BIGINT)]
    public int $userId;

    #[Column(name: 'chat_id', type: Types::BIGINT)]
    public int $chatId;

    #[Column(name: 'action_type', type: Types::STRING, enumType: AdminActionType::class)]
    public AdminActionType $actionType;

    /**
     * @var array<string, mixed>
     */
    #[Column(type: Types::JSON)]
    public array $data = [];

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $createdAt;

    #[Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    public function __construct()
    {
        $this->data = [];
        $this->createdAt = new DateTimeImmutable();
    }
}
