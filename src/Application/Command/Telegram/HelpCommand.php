<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram;

use App\Domain\Telegram\Command\TelegramCommandInterface;

class HelpCommand extends AbstractTelegramCommand implements TelegramCommandInterface
{
}
