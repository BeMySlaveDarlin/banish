<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin\Auth\Login;

use Symfony\Component\Validator\Constraints as Assert;

final class RequestDto
{
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotBlank]
        public string $token,
    ) {
    }
}
