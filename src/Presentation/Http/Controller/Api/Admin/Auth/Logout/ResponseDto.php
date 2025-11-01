<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin\Auth\Logout;

final class ResponseDto
{
    public function __construct(
        public bool $success = false,
    ) {
    }
}
