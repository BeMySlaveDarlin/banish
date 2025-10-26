<?php

declare(strict_types=1);

namespace App\Infrastructure\EventManager\Listener;

use App\Infrastructure\Metrics\RequestMetrics;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

#[AsEventListener(event: TerminateEvent::class, method: 'onTerminateApp')]
class RequestListener
{
    public function __construct(
        protected LoggerInterface $logger,
        protected RequestMetrics $requestMetrics
    ) {
    }

    public function onTerminateApp(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $requestData = [
            'params' => $request->toArray(),
            'headers' => $request->headers->all(),
        ];

        $logLevel = match (true) {
            $response->getStatusCode() >= 200 && $response->getStatusCode() < 400 => Level::Info,
            $response->getStatusCode() >= 400 && $response->getStatusCode() < 500 => Level::Warning,
            $response->getStatusCode() >= 500 => Level::Error,
            default => Level::Notice,
        };

        $this->logger->log($logLevel, 'HTTP Request', [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'request' => $requestData,
            'response' => $response->getContent(),
        ]);
    }
}
