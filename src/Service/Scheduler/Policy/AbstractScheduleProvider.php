<?php

declare(strict_types=1);

namespace App\Service\Scheduler\Policy;

use App\Component\Common\Entity\ScheduleRuleEntity;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

abstract class AbstractScheduleProvider implements ScheduleProviderInterface
{
    protected Schedule $schedule;

    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {
        $this->schedule = new Schedule();
    }

    abstract protected function getName();

    public function getSchedule(): Schedule
    {
        $query = "SELECT 1 FROM information_schema.tables WHERE table_name = 'queue_schedule_rule' AND table_type = 'BASE TABLE'";
        $exists = (bool) $this->entityManager->getConnection()->executeQuery($query);
        if ($exists) {
            $rules = $this->entityManager
                ->getRepository(ScheduleRuleEntity::class)
                ->findBy(['schedule' => $this->getName()]);

            foreach ($rules as $rule) {
                $recurringMessage = $this->getRecurringMessage($rule);
                if ($recurringMessage) {
                    $this->schedule->add($recurringMessage);
                }
            }
        }

        return $this->schedule;
    }

    private function getRecurringMessage(ScheduleRuleEntity $entity): ?RecurringMessage
    {
        $messageClass = $entity->message;
        $options = $entity->options->data;
        if (empty($options)) {
            $message = new $messageClass();
        } else {
            $message = new $messageClass(...$options);
        }

        if ($entity->type === 'cron') {
            return RecurringMessage::cron($entity->rule, $message, new DateTimeZone('UTC'));
        }

        if ($entity->type === 'every') {
            return RecurringMessage::every($entity->rule, $message);
        }

        return null;
    }
}
