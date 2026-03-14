<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Exception;

use App\Domain\Telegram\Enum\BanStatus;

final class InvalidBanStateTransitionException extends \DomainException
{
    public static function create(BanStatus $from, BanStatus $to): self
    {
        return new self(
            sprintf('Invalid ban state transition from "%s" to "%s"', $from->value, $to->value)
        );
    }
}
