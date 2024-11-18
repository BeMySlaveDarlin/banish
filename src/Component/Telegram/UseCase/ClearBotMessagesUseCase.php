<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatUserBanEntity;
use App\Component\Telegram\Policy\TelegramApiClientPolicy;
use App\Service\UseCase\NonTransactionalUseCaseInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class ClearBotMessagesUseCase implements NonTransactionalUseCaseInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private TelegramApiClientPolicy $apiClientPolicy
    ) {
    }

    public function execute(): void
    {
        $userBans = $this->getFinished();
        $this->clearBans($userBans);

        $userBans = $this->getOldPending();
        $this->clearBans($userBans);

        $this->entityManager->flush();
    }

    private function clearBans(array $userBans): void
    {
        foreach ($userBans as $userBan) {
            try {
                $this->apiClientPolicy->deleteMessage($userBan->chatId, $userBan->banMessageId);
                if (!empty($userBan->initialMessageId)) {
                    $this->apiClientPolicy->deleteMessage($userBan->chatId, $userBan->initialMessageId);
                }
            } catch (\Throwable $throwable) {
                $this->logger->info($throwable->getMessage(), ['userBan' => $userBan]);
            }

            $userBan->status = TelegramChatUserBanEntity::STATUS_DELETED;
            $this->entityManager->persist($userBan);
        }
    }

    /**
     * @return TelegramChatUserBanEntity[]
     */
    private function getFinished(): array
    {
        return $this->entityManager
            ->getRepository(TelegramChatUserBanEntity::class)
            ->findBy([
                'status' => [TelegramChatUserBanEntity::STATUS_CANCELED, TelegramChatUserBanEntity::STATUS_BANNED],
            ]);
    }

    /**
     * @return TelegramChatUserBanEntity[]
     * @throws \DateInvalidOperationException
     */
    private function getOldPending(): array
    {
        $date = (new \DateTimeImmutable())->sub(new \DateInterval('PT10M'));

        return $this->entityManager
            ->getRepository(TelegramChatUserBanEntity::class)
            ->findOldPending(TelegramChatUserBanEntity::STATUS_PENDING, $date);
    }
}
