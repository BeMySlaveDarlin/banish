<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram\Admin;

use App\Application\Command\Telegram\AbstractTelegramCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;

class RequestAdminLinkCommand extends AbstractTelegramCommand implements TelegramCommandInterface
{
}
