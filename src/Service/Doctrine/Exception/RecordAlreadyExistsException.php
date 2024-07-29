<?php

declare(strict_types=1);

namespace App\Service\Doctrine\Exception;

use Exception;
use Throwable;

class RecordAlreadyExistsException extends Exception
{
    public function __construct($message = 'Record already exists.', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
