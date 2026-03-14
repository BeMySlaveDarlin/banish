<?php

declare(strict_types=1);

namespace App\Presentation\Console\Command\Admin;

use App\Domain\Admin\Service\AdminSessionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:admin:generate-link',
    description: 'Generate admin panel login link for a user',
)]
final class GenerateAdminLinkCommand extends Command
{
    public function __construct(
        private readonly AdminSessionService $sessionService,
        private readonly string $adminPanelBaseUrl,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'Telegram user ID')
            ->addOption('ttl', 't', InputOption::VALUE_OPTIONAL, 'Session TTL in seconds', '3600');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $rawUserId */
        $rawUserId = $input->getArgument('userId');
        $userId = (int) $rawUserId;

        /** @var string $rawTtl */
        $rawTtl = $input->getOption('ttl');
        $ttl = (int) $rawTtl;

        if ($userId <= 0) {
            $output->writeln('<error>Invalid user ID</error>');

            return Command::FAILURE;
        }

        $session = $this->sessionService->getOrCreateSession($userId, $ttl);
        $token = $this->sessionService->createExchangeToken($userId, $session->id);

        $link = sprintf('%s/admin/auth/%s', rtrim($this->adminPanelBaseUrl, '/'), $token->id);

        $output->writeln('');
        $output->writeln(sprintf('<info>%s</info>', $link));
        $output->writeln('');
        $output->writeln(sprintf('User ID:  <comment>%d</comment>', $userId));
        $output->writeln(sprintf('Expires:  <comment>%s</comment> (5 min, single use)', $token->expiresAt->format('H:i:s')));

        return Command::SUCCESS;
    }
}
