<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

final readonly class TelegramWebhookService
{
    private const int API_DELAY_MICROSECONDS = 50000;

    public function __construct(
        private TelegramWebhookApiInterface $webhookApi
    ) {
    }

    public function clearUpdates(): void
    {
        $webhookInfo = $this->webhookApi->getWebhookInfo();
        $isDeleted = $this->webhookApi->deleteWebhook();

        usleep(self::API_DELAY_MICROSECONDS);
        if ($isDeleted) {
            $updates = $this->webhookApi->getUpdates();
            if (!empty($updates)) {
                /** @var array<string, mixed> $lastUpdate */
                $lastUpdate = array_pop($updates);
                /** @var int|string|null $rawUpdateId */
                $rawUpdateId = $lastUpdate['update_id'] ?? 0;
                $updateId = is_int($rawUpdateId) ? $rawUpdateId : (int) $rawUpdateId;
                $this->webhookApi->getUpdates(['offset' => $updateId + 1]);
            }
        }

        usleep(self::API_DELAY_MICROSECONDS);
        if ($webhookInfo && !empty($webhookInfo->url)) {
            $this->webhookApi->setWebhook($webhookInfo->url);
        }
    }
}
