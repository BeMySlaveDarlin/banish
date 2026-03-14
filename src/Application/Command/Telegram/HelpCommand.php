<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram;

use App\Infrastructure\Telegram\Attribute\AsTelegramCommand;

#[AsTelegramCommand('/help')]
#[AsTelegramCommand('/start')]
final class HelpCommand extends AbstractTelegramCommand
{
}
