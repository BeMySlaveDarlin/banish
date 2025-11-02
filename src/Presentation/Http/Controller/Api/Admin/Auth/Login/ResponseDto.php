<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin\Auth\Login;

use DateTimeInterface;

final readonly class ResponseDto
{
    public function __construct(
        public bool $success = false,
        public ?int $userId = null,
        public ?string $userName = null,
        public ?DateTimeInterface $expiresAt = null,
    ) {
    }
}
