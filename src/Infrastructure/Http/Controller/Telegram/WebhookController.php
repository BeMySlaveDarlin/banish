<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Telegram;

use App\Domain\Telegram\Service\HistoryService;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Dispatcher\Dispatcher;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class WebhookController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private Dispatcher $dispatcher,
        private HistoryService $historyService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/telegram/webhook/v2', name: 'telegram_webhook_v2', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        try {
            $update = $this->serializer->deserialize($request->getContent(), TelegramUpdate::class, 'json');
            $update->request = $request->toArray();
        } catch (Throwable $e) {
            $update = $this->serializer->deserialize('', TelegramUpdate::class, 'json');
        }

        try {
            $result = $this->dispatcher->dispatch($update);

            $this->logger->info('Webhook processing success', [
                'request' => $request->getContent(),
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            $result = $e->getMessage();

            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->getContent(),
            ]);
        }

        $this->historyService->createRequestHistory($update, $result);

        return new Response('OK', Response::HTTP_OK);
    }
}
