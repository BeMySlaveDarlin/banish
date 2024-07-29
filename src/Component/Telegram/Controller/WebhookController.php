<?php

declare(strict_types=1);

namespace App\Component\Telegram\Controller;

use App\Component\Telegram\Factory\TelegramUpdateFactory;
use App\Component\Telegram\Factory\TelegramUseCaseFactory;
use App\Component\Telegram\Policy\TelegramConfigPolicy;
use App\Service\Component\Controller\AbstractApiController;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends AbstractApiController
{
    public function handleAction(
        string $secret,
        TelegramConfigPolicy $configPolicy,
        TelegramUpdateFactory $messageFactory,
        TelegramUseCaseFactory $useCaseFactory
    ): Response {
        $configPolicy->validateSecret($secret);
        $update = $messageFactory->getUpdate();
        $useCase = $useCaseFactory->getUseCase($update);
        if ($useCase) {
            $this->useCaseHandler->handle($useCase);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
