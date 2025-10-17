<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

class TelegramWebhookService
{
    public function __construct(
        private TelegramApiService $telegramApiService
    ) {
    }

    public function clearUpdates(): void
    {
        $webhookInfo = $this->telegramApiService->getWebhookInfo();
        $isDeleted = $this->telegramApiService->deleteWebhook();

        usleep(50000);
        if ($isDeleted) {
            $updates = $this->telegramApiService->getUpdates();
            if (!empty($updates)) {
                $lastUpdate = array_pop($updates);
                $this->telegramApiService->getUpdates(['offset' => $lastUpdate->update_id + 1]);
            }
        }

        usleep(50000);
        if ($webhookInfo && !empty($webhookInfo->url)) {
            $this->telegramApiService->setWebhook($webhookInfo->url);
        }
    }
}
