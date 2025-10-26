<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Enum;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case BANNED = 'banned';
    case LEFT = 'left';
}
