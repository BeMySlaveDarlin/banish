<?php

declare(strict_types=1);

namespace App\Infrastructure\EventManager\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsEventListener(event: ExceptionEvent::class, method: 'onKernelException')]
final class KernelErrorListener
{
    private const string TELEGRAM_WEBHOOK_PREFIX = '/api/telegram/';

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        $isTelegramWebhook = str_starts_with($pathInfo, self::TELEGRAM_WEBHOOK_PREFIX);

        $code = $throwable instanceof HttpExceptionInterface
            ? $throwable->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $logContext = [
            'error' => true,
            'message' => $throwable->getMessage(),
            'path' => $pathInfo,
            'method' => $request->getMethod(),
        ];

        if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] !== 'prod') {
            $logContext['error_code'] = $code;
            $logContext['error_trace'] = $throwable->getTrace();
        }

        $this->logger->warning('onKernelException', $logContext);

        $event->allowCustomResponseCode();

        if ($isTelegramWebhook) {
            $event->setResponse(new Response('OK', Response::HTTP_OK));

            return;
        }

        $event->setResponse(new JsonResponse(
            ['error' => true, 'message' => $throwable->getMessage()],
            $code
        ));
    }
}
