<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram\Reaction;

use App\Application\Command\Telegram\AbstractTelegramCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;

class StartBanByReactionCommand extends AbstractTelegramCommand implements TelegramCommandInterface
{
}
