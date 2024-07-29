<?php

declare(strict_types=1);

namespace App\Component\Common\Command;

use App\Component\Common\UseCase\RefreshDbPartitionsUseCase;
use App\Service\Component\Command\AbstractConsoleCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:common:refresh-db-partitions')]
class RefreshDbPartitionsCommand extends AbstractConsoleCommand
{
    protected static $defaultName = 'app:common:refresh-db-partitions';

    protected function configure(): void
    {
        $this->setDescription('Refresh Db Partitions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->useCaseHandler->handle(
            new RefreshDbPartitionsUseCase($this->entityManager->getConnection(), $this->parameters)
        );

        return Command::SUCCESS;
    }
}
