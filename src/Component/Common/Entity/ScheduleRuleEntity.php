<?php

namespace App\Component\Common\Entity;

use App\Component\Common\Repository\ScheduleRuleRepository;
use App\Service\Doctrine\Type\JsonBValue;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: ScheduleRuleRepository::class)]
#[Index(columns: ['schedule'], name: 'idx_queue_schedule_rule_schedule')]
#[Index(columns: ['rule'], name: 'idx_queue_schedule_rule_rule')]
#[Index(columns: ['type'], name: 'idx_queue_schedule_rule_type')]
#[Index(columns: ['message'], name: 'idx_queue_schedule_rule_message')]
#[Table(name: '`queue_schedule_rule`')]
class ScheduleRuleEntity
{
    #[Id]
    #[GeneratedValue(strategy: "SEQUENCE")]
    #[SequenceGenerator(sequenceName: "queue_schedule_rule_id_seq", allocationSize: 1, initialValue: 1)]
    #[Column(type: Types::BIGINT)]
    public string $id;
    #[Column(type: Types::STRING, length: 255)]
    public string $schedule;
    #[Column(type: Types::STRING, length: 255)]
    public string $rule;
    #[Column(type: Types::STRING, length: 255)]
    public string $type;
    #[Column(type: Types::STRING, length: 255)]
    public string $message;
    #[Column(type: "jsonb", nullable: true, options: ["jsonb" => true])]
    public ?JsonBValue $options = null;
    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $createdAt;
}
