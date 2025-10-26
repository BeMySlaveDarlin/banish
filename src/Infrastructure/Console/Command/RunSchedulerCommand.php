<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Command;

use App\Domain\Common\Entity\ScheduleRuleEntity;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'scheduler:run',
    description: 'Run scheduler and dispatch due messages to message bus',
)]
class RunSchedulerCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Scheduler started. Running every 5 seconds...');

        /** @phpstan-ignore-next-line */
        while (true) {
            try {
                $rules = $this->entityManager
                    ->getRepository(ScheduleRuleEntity::class)
                    ->findAll();

                if (!empty($rules)) {
                    $now = new DateTime();
                    $dispatchedCount = 0;

                    foreach ($rules as $rule) {
                        if ($this->shouldRunTask($rule, $now)) {
                            try {
                                $messageClass = $rule->message;
                                $options = $rule->options?->toArray() ?? ['data' => null];

                                if (empty($options) || empty($options['data'])) {
                                    $message = new $messageClass();
                                } else {
                                    $message = new $messageClass(...$options);
                                }

                                $this->messageBus->dispatch($message);
                                $this->logger->info(
                                    'Dispatched scheduled message',
                                    [
                                        'message' => $rule->message,
                                        'schedule' => $rule->schedule,
                                        'rule' => $rule->rule,
                                    ]
                                );
                                $dispatchedCount++;
                            } catch (\Throwable $e) {
                                $this->logger->error(
                                    'Failed to dispatch scheduled message',
                                    [
                                        'message' => $rule->message,
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString(),
                                    ]
                                );
                            }
                        }
                    }

                    if ($dispatchedCount > 0) {
                        $this->logger->debug(sprintf('Dispatched %d message(s)', $dispatchedCount));
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Scheduler error',
                    [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }

            sleep(30);
        }
    }

    private function shouldRunTask(ScheduleRuleEntity $rule, DateTime $now): bool
    {
        if ($rule->type === 'cron') {
            return $this->isCronDue($rule->rule, $now);
        }

        if ($rule->type === 'every') {
            return true;
        }

        return false;
    }

    private function isCronDue(string $expression, DateTime $dateTime): bool
    {
        $parts = explode(' ', trim($expression));

        if (count($parts) < 5) {
            return false;
        }

        [$minute, $hour, $dayOfMonth, $month, $dayOfWeek] = $parts;

        if (!$this->matchesCronPart($dateTime->format('i'), $minute, 0, 59)) {
            return false;
        }
        if (!$this->matchesCronPart($dateTime->format('H'), $hour, 0, 23)) {
            return false;
        }
        if (!$this->matchesCronPart($dateTime->format('d'), $dayOfMonth, 1, 31)) {
            return false;
        }
        if (!$this->matchesCronPart($dateTime->format('m'), $month, 1, 12)) {
            return false;
        }
        if (!$this->matchesCronPart($dateTime->format('w'), $dayOfWeek, 0, 6)) {
            return false;
        }

        return true;
    }

    private function matchesCronPart(string | int $value, string $expression, int $min, int $max): bool
    {
        $value = (int) $value;

        if ($expression === '*') {
            return true;
        }

        if (str_contains($expression, '-')) {
            [$rangeMin, $rangeMax] = explode('-', $expression);

            return $value >= (int) $rangeMin && $value <= (int) $rangeMax;
        }

        if (str_contains($expression, '/')) {
            [$range, $step] = explode('/', $expression);
            $step = (int) $step;

            if ($range === '*') {
                return $value % $step === 0;
            }

            if (str_contains($range, '-')) {
                [$rangeMin, $rangeMax] = explode('-', $range);
                $rangeMin = (int) $rangeMin;
                $rangeMax = (int) $rangeMax;

                return $value >= $rangeMin && $value <= $rangeMax && ($value - $rangeMin) % $step === 0;
            }

            return false;
        }

        if (str_contains($expression, ',')) {
            $values = explode(',', $expression);

            return in_array((string) $value, $values, true);
        }

        return $value === (int) $expression;
    }
}
