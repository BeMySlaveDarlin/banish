<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SecretValidator
{
    public function __construct(
        private readonly string $appSecret
    ) {
    }

    public function validate(string $secret): void
    {
        if ($secret !== $this->appSecret) {
            throw new BadRequestException('Invalid secret');
        }
    }
}
