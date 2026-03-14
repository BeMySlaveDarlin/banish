<?php

declare(strict_types=1);

namespace App\Presentation\Console\Command\Admin;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\TelegramChatMemberApiInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:admin:ban-user',
    description: 'Ban a user in a chat via Telegram API',
)]
final class BanUserCommand extends Command
{
    public function __construct(
        private readonly TelegramChatMemberApiInterface $chatMemberApi,
        private readonly ChatRepository $chatRepository,
        private readonly UserRepository $userRepository,
        private readonly BanRepository $banRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('chatId', InputArgument::REQUIRED, 'Telegram chat ID')
            ->addArgument('userId', InputArgument::REQUIRED, 'Telegram user ID to ban');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $rawChatId */
        $rawChatId = $input->getArgument('chatId');
        $chatId = (int) $rawChatId;

        /** @var string $rawUserId */
        $rawUserId = $input->getArgument('userId');
        $userId = (int) $rawUserId;

        $chat = $this->chatRepository->findByChatId($chatId);
        if ($chat === null) {
            $output->writeln("<error>Chat $chatId not found in database</error>");

            return Command::FAILURE;
        }

        $user = $this->userRepository->findByChatAndUser($chatId, $userId);
        $userName = $user?->name ?? $user?->username ?? "User #$userId";

        $output->writeln('');
        $output->writeln(sprintf('Chat:  <comment>%s</comment> (%d)', $chat->name ?? 'Unknown', $chatId));
        $output->writeln(sprintf('User:  <comment>%s</comment> (%d)', $userName, $userId));
        $output->writeln('');

        $result = $this->chatMemberApi->banChatMember($chatId, $userId);

        if (!$result) {
            $output->writeln('<error>Failed to ban user via Telegram API</error>');

            return Command::FAILURE;
        }

        $ban = TelegramChatUserBanEntity::create(
            chatId: $chatId,
            reporterId: 0,
            spammerId: $userId,
            banMessageId: 0,
        );
        $this->banRepository->save($ban);

        $ban->markAsBanned();
        $this->banRepository->save($ban);

        $output->writeln('<info>User banned successfully (Telegram API + DB record created)</info>');

        return Command::SUCCESS;
    }
}
