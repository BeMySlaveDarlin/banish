<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatUserBanEntity;
use App\Component\Telegram\Policy\TelegramApiClientPolicy;
use App\Service\UseCase\NonTransactionalUseCaseInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class ClearBotMessagesUseCase implements NonTransactionalUseCaseInterface
{
    public function __construct(
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
            } catch (\Throwable $exception) {
                //Suppress old messages deletion errors
            }
            $userBan->status = TelegramChatUserBanEntity::STATUS_DELETED;
            $this->entityManager->persist($userBan);
        }
    }

    private function getFinished(): array
    {
        return $this->entityManager
            ->getRepository(TelegramChatUserBanEntity::class)
            ->findBy([
                'status' => [TelegramChatUserBanEntity::STATUS_CANCELED, TelegramChatUserBanEntity::STATUS_BANNED],
            ]);
    }

    private function getOldPending(): array
    {
        $date = (new \DateTimeImmutable())->sub(new \DateInterval('PT10M'));

        return $this->entityManager
            ->getRepository(TelegramChatUserBanEntity::class)
            ->findOldPending(TelegramChatUserBanEntity::STATUS_PENDING, $date);
    }
}
