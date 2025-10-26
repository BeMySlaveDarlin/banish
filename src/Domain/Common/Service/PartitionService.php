<?php

declare(strict_types=1);

namespace App\Domain\Common\Service;

use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PartitionService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ParameterBagInterface $parameters
    ) {
    }

    public function refreshPartitions(): void
    {
        $now = new DateTimeImmutable();
        $prevMonth = $now->sub(new DateInterval('P3M'));
        $nextMonth = $now->add(new DateInterval('P3M'));

        $tables = $this->parameters->get('app.partitioned_tables');
        if (!is_array($tables)) {
            return;
        }
        foreach ($tables as $table => $params) {
            if (!$this->isTableExists($table)) {
                continue;
            }
            $this->dropPartition($table, $now, $prevMonth);

            $month = $now->sub(new DateInterval('P3M'));
            while ($month->format('m') !== $nextMonth->format('m')) {
                $this->addPartition($table, $month);
                $month = $month->add(new DateInterval('P1M'));
            }
            $this->addPartition($table, $nextMonth);
        }
    }

    private function dropPartition(string $table, DateTimeImmutable $now, DateTimeImmutable $prevMonth): void
    {
        $month = $now->sub(new DateInterval('P12M'));
        while ($prevMonth->format('m') !== $month->format('m')) {
            $monthNum = $month->format('m');
            $yearNum = $month->format('Y');

            $sql = "DROP TABLE IF EXISTS partitions.{$table}_y{$yearNum}m{$monthNum};";
            $this->connection->executeStatement($sql);
            $month = $month->add(new DateInterval('P1M'));
        }
    }

    private function addPartition(string $table, DateTimeImmutable $date): void
    {
        $start = $date->format('Y-m-01');
        $monthNum = $date->format('m');
        $yearNum = $date->format('Y');
        $finish = $date->add(new DateInterval('P1M'))->format('Y-m-01');
        $sql = "CREATE TABLE IF NOT EXISTS partitions.{$table}_y{$yearNum}m{$monthNum} 
                PARTITION OF public.{$table} FOR VALUES FROM ('{$start}') TO ('{$finish}');";
        $this->connection->executeStatement($sql);
    }

    private function isTableExists(string $table): bool
    {
        $query = "SELECT 1 FROM information_schema.tables WHERE table_name = '$table' AND table_type = 'BASE TABLE'";

        return (bool) $this->connection->executeQuery($query)->fetchOne();
    }
}
