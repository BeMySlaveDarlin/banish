<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram\Ban;

use App\Application\Command\Telegram\AbstractTelegramCommand;
use App\Infrastructure\Telegram\Attribute\AsTelegramCommand;

#[AsTelegramCommand('/ban')]
final class StartBanCommand extends AbstractTelegramCommand
{
}
