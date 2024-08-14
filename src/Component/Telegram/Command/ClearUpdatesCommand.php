<?php

declare(strict_types=1);

namespace App\Component\Telegram\Command;

use App\Component\Telegram\Policy\TelegramApiClientPolicy;
use App\Component\Telegram\UseCase\ClearTelegramUpdatesUseCase;
use App\Service\Component\Command\AbstractConsoleCommand;
use App\Service\UseCase\UseCaseHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(name: 'app:telegram:clear-updates')]
class ClearUpdatesCommand extends AbstractConsoleCommand
{
    public function __construct(
        LoggerInterface $logger,
        CacheInterface $cache,
        EntityManagerInterface $entityManager,
        UseCaseHandler $useCaseHandler,
        ParameterBagInterface $parameters,
        private readonly TelegramApiClientPolicy $telegramApiClientPolicy
    ) {
        parent::__construct($logger, $cache, $entityManager, $useCaseHandler, $parameters);
    }

    protected function configure(): void
    {
        $this->setDescription('Clear telegram updates');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->useCaseHandler->handle(
            new ClearTelegramUpdatesUseCase($this->telegramApiClientPolicy)
        );

        return Command::SUCCESS;
    }
}
