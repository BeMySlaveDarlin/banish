<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler\Telegram;

use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\TelegramApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SyncChatUsersHandler
{
    public function __construct(
        private readonly ChatRepository $chatRepository,
        private readonly UserRepository $userRepository,
        private readonly TelegramApiService $telegramApiService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(SyncChatUsersMessage $message): void
    {
        $this->logger->info('Starting sync of chat users');

        $chats = $this->chatRepository->findAll();

        foreach ($chats as $chat) {
            try {
                $this->syncChatMembers($chat->chatId);
            } catch (\Exception $e) {
                $this->logger->error("Failed to sync users for chat {$chat->chatId}: {$e->getMessage()}");
            }
        }

        $this->logger->info('Finished sync of chat users');
    }

    private function syncChatMembers(int $chatId): void
    {
        $dbUsers = $this->userRepository->findBy(['chatId' => $chatId]);

        $totalUsers = count($dbUsers);
        if ($totalUsers === 0) {
            $this->logger->info("Chat {$chatId}: no users to sync");

            return;
        }

        $batchSize = 20;
        $batches = array_chunk($dbUsers, $batchSize);

        $syncedCount = 0;
        $removedCount = 0;
        $errorCount = 0;

        foreach ($batches as $batch) {
            foreach ($batch as $dbUser) {
                try {
                    $member = $this->telegramApiService->getChatMemberFromApi($chatId, $dbUser->userId);

                    if ($member === null) {
                        $this->userRepository->remove($dbUser);
                        $this->logger->debug("Removed user {$dbUser->userId} from chat {$chatId}");
                        $removedCount++;
                        continue;
                    }

                    if ($member->status === 'kicked' || $member->status === 'left') {
                        $this->userRepository->remove($dbUser);
                        $removedCount++;
                        continue;
                    }

                    $syncedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->logger->warning("Error checking user {$dbUser->userId} in chat {$chatId}: {$e->getMessage()}");
                }
            }

            if (count($batches) > 1) {
                usleep(100000);
            }
        }

        $this->logger->info("Chat {$chatId}: synced {$syncedCount} users, removed {$removedCount}, {$errorCount} errors out of {$totalUsers} total");
    }
}
