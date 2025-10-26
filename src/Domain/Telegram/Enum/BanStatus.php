<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Enum;

enum BanStatus: string
{
    case PENDING = 'pending';
    case BANNED = 'banned';
    case CANCELED = 'canceled';
    case DELETED = 'deleted';
}
