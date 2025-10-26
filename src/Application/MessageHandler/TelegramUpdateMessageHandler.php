<?php

declare(strict_types=1);

namespace App\Application\MessageHandler;

use App\Application\Message\TelegramUpdateMessage;
use App\Domain\Telegram\Service\HistoryService;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Dispatcher\Dispatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

#[AsMessageHandler]
final class TelegramUpdateMessageHandler
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly Dispatcher $dispatcher,
        private readonly HistoryService $historyService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(TelegramUpdateMessage $message): void
    {
        try {
            $update = $this->serializer->deserialize($message->updateJson, TelegramUpdate::class, 'json');
            /** @var array<string, mixed>|null $requestData */
            $requestData = json_decode($message->updateJson, true, 512, JSON_THROW_ON_ERROR);
            $update->request = $requestData;
        } catch (Throwable $e) {
            $this->logger->error('Failed to deserialize telegram update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $message->updateJson,
            ]);

            return;
        }

        try {
            $result = $this->dispatcher->dispatch($update);

            $this->logger->info('Webhook processing success', [
                'result' => $result,
                'update_id' => $update->update_id,
            ]);
        } catch (Throwable $e) {
            $result = $e->getMessage();

            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'update_id' => $update->update_id,
            ]);
        }

        $this->historyService->createRequestHistory($update, $result);
    }
}
