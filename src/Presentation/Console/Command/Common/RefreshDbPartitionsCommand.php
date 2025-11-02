<?php

declare(strict_types=1);

namespace App\Presentation\Console\Command\Common;

use App\Domain\Common\Service\PartitionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:common:refresh-db-partitions',
    description: 'Refresh DB Partitions',
)]
class RefreshDbPartitionsCommand extends Command
{
    public function __construct(
        private readonly PartitionService $partitionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->partitionService->refreshPartitions();

        $output->writeln('<info>Partitions refreshed successfully</info>');

        return Command::SUCCESS;
    }
}
