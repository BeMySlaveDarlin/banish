<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Telegram;

use App\Application\Message\TelegramUpdateMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class WebhookController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function webhook(Request $request): Response
    {
        try {
            $content = $request->getContent();
            $data = json_decode($content, true);

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
                'trace' => $e->getTraceAsString(),
                'request' => $request->getContent(),
            ]);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
