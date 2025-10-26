<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Exception;

use Exception;
use Throwable;

class RecordAlreadyExistsException extends Exception
{
    public function __construct(
        string $message = 'Record already exists.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
