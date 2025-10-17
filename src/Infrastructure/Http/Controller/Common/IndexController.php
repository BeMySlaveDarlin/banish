<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Common;

use App\Infrastructure\Http\Controller\AbstractApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractApiController
{
    public function handleAction(): Response
    {
        return new JsonResponse(
            [
                'error' => false,
                'message' => 'It works!',
                'env' => $this->getParameter('app.env'),
            ]
        );
    }
}
