<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin\Auth\Validate;

use DateTimeInterface;

final class ResponseDto
{
    public function __construct(
        public bool $valid = false,
        public ?int $userId = null,
        public ?string $userName = null,
        public ?DateTimeInterface $expiresAt = null,
    ) {
    }
}
