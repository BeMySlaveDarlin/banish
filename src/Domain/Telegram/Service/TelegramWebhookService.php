<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

class TelegramWebhookService
{
    public function __construct(
        private readonly TelegramApiService $telegramApiService
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
                /** @var array<string, mixed> $lastUpdate */
                $lastUpdate = array_pop($updates);
                /** @var int|string|null $rawUpdateId */
                $rawUpdateId = $lastUpdate['update_id'] ?? 0;
                $updateId = is_int($rawUpdateId) ? $rawUpdateId : (int) $rawUpdateId;
                $this->telegramApiService->getUpdates(['offset' => $updateId + 1]);
            }
        }

        usleep(50000);
        if ($webhookInfo && !empty($webhookInfo->url)) {
            $this->telegramApiService->setWebhook($webhookInfo->url);
        }
    }
}
