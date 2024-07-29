<?php

declare(strict_types=1);

namespace App\Service\EventManager\Listener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsEventListener(event: ExceptionEvent::class, method: 'onKernelException')]
class KernelErrorListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $code = $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        $params = [
            'error' => true,
            'message' => $throwable->getMessage(),
        ];
        if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] !== 'prod') {
            $params['error_code'] = $code;
            $params['error_trace'] = $throwable->getTrace();
        }

        $response = new JsonResponse($params, $code);
        if ($throwable instanceof HttpExceptionInterface) {
            $response->headers->replace($throwable->getHeaders());
        }

        $event->setResponse($response);
    }
}
