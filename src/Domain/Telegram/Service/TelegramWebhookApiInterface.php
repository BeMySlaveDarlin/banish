<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\ValueObject\Bot\TelegramWebHookInfo;

interface TelegramWebhookApiInterface
{
    public function getWebhookInfo(): ?TelegramWebHookInfo;

    public function deleteWebhook(): bool;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getUpdates(array $params = []): array;

    public function setWebhook(string $url): bool;
}
