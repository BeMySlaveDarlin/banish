<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram\Admin;

use App\Application\Command\Telegram\AbstractTelegramCommand;
use App\Infrastructure\Telegram\Attribute\AsTelegramCommand;

#[AsTelegramCommand('/admin')]
final class RequestAdminLinkCommand extends AbstractTelegramCommand
{
}
