<?php

declare(strict_types=1);

namespace App\Component\Telegram\Schedule;

use App\Component\Telegram\Policy\TelegramApiClientPolicy;
use App\Component\Telegram\UseCase\ClearBotMessagesUseCase;
use App\Service\UseCase\UseCaseHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ClearBotMessagesHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TelegramApiClientPolicy $apiClientPolicy,
        private UseCaseHandler $useCaseHandler
    ) {
    }

    public function __invoke(ClearBotMessagesMessage $message): void
    {
        $this->useCaseHandler->handle(
            new ClearBotMessagesUseCase($this->entityManager, $this->apiClientPolicy)
        );
    }
}
