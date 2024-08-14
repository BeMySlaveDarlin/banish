<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Policy\TelegramApiClientPolicy;
use App\Service\UseCase\UseCaseInterface;

class ClearTelegramUpdatesUseCase implements UseCaseInterface
{
    public function __construct(
        private TelegramApiClientPolicy $telegramApiClientPolicy
    ) {
    }

    public function execute(): void
    {
        $webhookInfo = $this->telegramApiClientPolicy->getWebhookInfo();
        $isDeleted = $this->telegramApiClientPolicy->deleteWebhook();
        if ($isDeleted) {
            $updates = $this->telegramApiClientPolicy->getUpdates();
            if (!empty($updates)) {
                $lastUpdate = array_pop($updates);
                $this->telegramApiClientPolicy->getUpdates(['offset' => $lastUpdate->update_id + 1]);
            }
        }
        if ($webhookInfo && !empty($webhookInfo->url)) {
            $this->telegramApiClientPolicy->setWebhook($webhookInfo->url);
        }
    }
}
