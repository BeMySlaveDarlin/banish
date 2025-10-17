<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Enum;

enum VoteType: string
{
    case BAN = 'ban';
    case FORGIVE = 'forgive';
}
