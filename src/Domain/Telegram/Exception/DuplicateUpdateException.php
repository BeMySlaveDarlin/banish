<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Exception;

use RuntimeException;

class DuplicateUpdateException extends RuntimeException
{
    public function __construct(int $updateId)
    {
        parent::__construct("Duplicate update: $updateId");
    }
}
