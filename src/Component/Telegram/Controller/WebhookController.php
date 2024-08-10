<?php

declare(strict_types=1);

namespace App\Component\Telegram\Controller;

use App\Component\Telegram\Factory\TelegramUseCaseFactory;
use App\Component\Telegram\Policy\TelegramConfigPolicy;
use App\Component\Telegram\ValueObject\TelegramUpdate;
use App\Service\Component\Controller\AbstractApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

class WebhookController extends AbstractApiController
{
    public function handleAction(
        string $secret,
        TelegramConfigPolicy $configPolicy,
        TelegramUseCaseFactory $useCaseFactory,
        Request $request,
        #[MapRequestPayload] TelegramUpdate $update
    ): Response {
        try {
            $configPolicy->validateSecret($secret);
            $update->request = $request;
            $useCase = $useCaseFactory->getUseCase($update);
            if ($useCase) {
                $this->useCaseHandler->handle($useCase);
            }
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage(), ['request' => $request->toArray()]);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
