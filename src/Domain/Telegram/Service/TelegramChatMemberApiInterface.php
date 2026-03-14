<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\ValueObject\Bot\TelegramChatMember;

interface TelegramChatMemberApiInterface
{
    public function getChatMember(?int $chatId, ?int $userId): ?TelegramChatMember;

    public function getChatMemberFromApi(?int $chatId, ?int $userId): ?TelegramChatMember;

    public function banChatMember(int $chatId, int $userId): bool;

    public function unbanChatMember(int $chatId, int $userId): bool;
}
