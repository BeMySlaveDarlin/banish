<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram\Ban;

use App\Application\Command\Telegram\AbstractTelegramCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;

class VoteForBanCommand extends AbstractTelegramCommand implements TelegramCommandInterface
{
}
