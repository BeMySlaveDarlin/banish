<?php

declare(strict_types=1);

namespace App\Presentation\Console\Command\Telegram;

use App\Domain\Telegram\Service\TelegramWebhookService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:telegram:clear-updates',
    description: 'Clear Telegram Updates',
)]
class ClearUpdatesCommand extends Command
{
    public function __construct(
        private readonly TelegramWebhookService $webhookService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->webhookService->clearUpdates();

        $output->writeln('<info>Telegram updates cleared successfully</info>');

        return Command::SUCCESS;
    }
}
