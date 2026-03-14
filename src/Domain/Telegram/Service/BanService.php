<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;

final readonly class BanService implements BanServiceInterface
{
    public function __construct(
        private BanRepository $banRepository,
        private VoteRepository $voteRepository,
        private ChatConfigServiceInterface $chatConfigService,
        private TelegramChatMemberApiInterface $chatMemberApi,
        private TelegramMessageApiInterface $messageApi,
        private RequestHistoryRepository $requestHistoryRepository,
        private LoggerInterface $logger
    ) {
    }

    public function banUser(TelegramChatEntity $chat, TelegramChatUserBanEntity $ban): void
    {
        if (!$ban->isPending()) {
            return;
        }

        $deleteOnlyMessage = $this->chatConfigService->isDeleteOnlyEnabled($chat);
        if (!$deleteOnlyMessage) {
            $banned = $this->chatMemberApi->banChatMember($chat->chatId, $ban->spammerId);
            if (!$banned) {
                $this->logger->error('Failed to ban user via Telegram API', [
                    'chatId' => $chat->chatId,
                    'spammerId' => $ban->spammerId,
                ]);

                return;
            }
            $ban->markAsBanned();
        } else {
            $ban->markAsForgiven();
        }

        if ($this->chatConfigService->isDeleteMessagesEnabled($chat)) {
            if ($deleteOnlyMessage && $ban->spamMessageId) {
                $this->deleteMessage($chat->chatId, $ban->spamMessageId);
            } else {
                $this->deleteSpammerMessages($chat->chatId, $ban->spammerId);
            }
        }

        try {
            $this->banRepository->save($ban);
        } catch (OptimisticLockException $e) {
            $this->logger->warning('Optimistic lock conflict in banUser, skipping', [
                'banId' => $ban->id,
                'chatId' => $chat->chatId,
                'spammerId' => $ban->spammerId,
            ]);
        }
    }

    public function forgiveBan(TelegramChatUserBanEntity $ban): void
    {
        $ban->markAsForgiven();
        $this->banRepository->save($ban);
    }

    /**
     * @param array<int, TelegramChatUserBanEntity> $bans
     */
    public function adminUnban(int $chatId, int $userId, array $bans): void
    {
        $this->chatMemberApi->unbanChatMember($chatId, $userId);

        foreach ($bans as $ban) {
            $this->voteRepository->deleteByBan($ban, flush: false);
            $this->banRepository->remove($ban, flush: false);
        }
        $this->voteRepository->flush();
        $this->banRepository->flush();
    }

    private function deleteMessage(int $chatId, int $messageId): void
    {
        try {
            $this->messageApi->deleteMessage((int) $chatId, (int) $messageId);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to delete spam message', [
                'chatId' => $chatId,
                'messageId' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deleteSpammerMessages(int $chatId, int $spammerId): void
    {
        $oneHourAgo = (new \DateTimeImmutable())->modify('-1 hour');
        $messageIds = $this->requestHistoryRepository->getMessageIdsByFromId($chatId, $spammerId, $oneHourAgo);

        foreach ($messageIds as $messageId) {
            $this->deleteMessage((int) $chatId, (int) $messageId);
        }
    }
}
