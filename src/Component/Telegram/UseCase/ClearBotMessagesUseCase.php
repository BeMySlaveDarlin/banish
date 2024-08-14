<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatUserBanEntity;
use App\Component\Telegram\Policy\TelegramApiClientPolicy;
use App\Service\UseCase\NonTransactionalUseCaseInterface;
use Doctrine\ORM\EntityManagerInterface;

class ClearBotMessagesUseCase implements NonTransactionalUseCaseInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TelegramApiClientPolicy $apiClientPolicy
    ) {
    }

    public function execute(): void
    {
        $userBans = $this->entityManager
            ->getRepository(TelegramChatUserBanEntity::class)
            ->findBy([
                'status' => [TelegramChatUserBanEntity::STATUS_CANCELED, TelegramChatUserBanEntity::STATUS_BANNED],
            ]);

        foreach ($userBans as $userBan) {
            try {
                $this->apiClientPolicy->deleteMessage($userBan->chatId, $userBan->banMessageId);
            } catch (\Throwable $exception) {
                //Suppress old messages deletion errors
            }
            $userBan->status = TelegramChatUserBanEntity::STATUS_DELETED;
            $this->entityManager->persist($userBan);
        }

        $this->entityManager->flush();
    }
}
