<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\ValueObject\ResponseMessages;

class UnsupportedHandler implements TelegramHandlerInterface
{
    /**
     * @param UnsupportedCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        return ResponseMessages::MESSAGE_NOT_SUPPORTED;
    }
}
