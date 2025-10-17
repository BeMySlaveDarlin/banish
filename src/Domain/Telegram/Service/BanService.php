<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Repository\BanRepository;

class BanService
{
    public function __construct(
        private BanRepository $banRepository,
        private ChatConfigService $chatConfigService,
        private TelegramApiService $telegramApiService
    ) {
    }

    public function banUser(TelegramChatEntity $chat, TelegramChatUserBanEntity $ban): void
    {
        $ban->status = BanStatus::BANNED;
        $this->banRepository->save($ban);

        $this->telegramApiService->banChatMember($chat->chatId, $ban->spammerId);

        if ($this->chatConfigService->isDeleteMessagesEnabled($chat) && $ban->spamMessageId) {
            $this->telegramApiService->deleteMessage($chat->chatId, $ban->spamMessageId);
        }
    }

    public function forgiveBan(TelegramChatUserBanEntity $ban): void
    {
        $ban->status = BanStatus::CANCELED;
        $this->banRepository->save($ban);
    }
}
