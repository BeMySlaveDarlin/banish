<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;

interface BanServiceInterface
{
    public function banUser(TelegramChatEntity $chat, TelegramChatUserBanEntity $ban): void;

    public function forgiveBan(TelegramChatUserBanEntity $ban): void;

    /**
     * @param array<int, TelegramChatUserBanEntity> $bans
     */
    public function adminUnban(int $chatId, int $userId, array $bans): void;
}
