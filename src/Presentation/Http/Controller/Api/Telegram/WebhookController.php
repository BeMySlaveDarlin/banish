<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Telegram;

use App\Application\Message\TelegramUpdateMessage;
use App\Infrastructure\Security\SecretValidator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Throwable;

final class WebhookController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly SecretValidator $secretValidator,
        private readonly RateLimiterFactory $webhookLimiter,
    ) {
    }

    public function webhook(Request $request, string $secret): Response
    {
        try {
            $this->secretValidator->validate($secret);
        } catch (Throwable) {
            return new Response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $limiter = $this->webhookLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume()->isAccepted()) {
            return new Response('OK', Response::HTTP_OK);
        }

        $data = null;

        try {
            $content = $request->getContent();
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                return new Response('OK', Response::HTTP_OK);
            }

            $updateType = 'unknown';
            if (isset($data['message'])) {
                $updateType = 'message';
            } elseif (isset($data['callback_query'])) {
                $updateType = 'callback_query';
            } elseif (isset($data['message_reaction'])) {
                $updateType = 'message_reaction';
            } elseif (isset($data['message_reaction_count'])) {
                $updateType = 'message_reaction_count';
            } elseif (isset($data['my_chat_member'])) {
                $updateType = 'my_chat_member';
            }

            $message = new TelegramUpdateMessage($content);
            $this->messageBus->dispatch($message);

            $this->logger->info('Telegram update dispatched to queue', [
                'update_type' => $updateType,
                'request_size' => strlen($content),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to dispatch telegram update to queue', [
                'error' => $e->getMessage(),
                'update_id' => is_array($data) ? ($data['update_id'] ?? null) : null,
            ]);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
