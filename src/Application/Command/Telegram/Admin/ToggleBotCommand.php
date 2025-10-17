<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram\Admin;

use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

class ToggleBotCommand implements TelegramCommandInterface
{
    public TelegramUpdate $update;
    public ?TelegramChatEntity $chat = null;
    public ?TelegramChatUserEntity $user = null;

    public function __construct(TelegramUpdate $update)
    {
        $this->update = $update;
    }
}
