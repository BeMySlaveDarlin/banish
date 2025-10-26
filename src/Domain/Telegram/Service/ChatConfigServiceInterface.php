<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;

interface ChatConfigServiceInterface
{
    public function flush(): void;

    public function getMinMessagesForTrust(TelegramChatEntity $chat): int;

    public function setMinMessagesForTrust(TelegramChatEntity $chat, int $value): void;

    public function getVotesRequired(TelegramChatEntity $chat): int;

    public function setVotesRequired(TelegramChatEntity $chat, int $value): void;

    public function isDeleteMessagesEnabled(TelegramChatEntity $chat): bool;

    public function setDeleteMessagesEnabled(TelegramChatEntity $chat, bool $enabled): void;

    public function isReactionsEnabled(TelegramChatEntity $chat): bool;

    public function setReactionsEnabled(TelegramChatEntity $chat, bool $enabled): void;

    public function isDeleteOnlyEnabled(TelegramChatEntity $chat): bool;

    public function setDeleteOnlyEnabled(TelegramChatEntity $chat, bool $enabled): void;

    public function getBanEmoji(TelegramChatEntity $chat): string;

    public function setBanEmoji(TelegramChatEntity $chat, string $emoji): void;

    public function getForgiveEmoji(TelegramChatEntity $chat): string;

    public function setForgiveEmoji(TelegramChatEntity $chat, string $emoji): void;
}
