<?php

declare(strict_types=1);

namespace App\Infrastructure\EventManager\Listener;

use App\Infrastructure\Metrics\RequestMetricsFactory;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

#[AsEventListener(event: TerminateEvent::class, method: 'onTerminateApp')]
final class RequestListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestMetricsFactory $metricsFactory
    ) {
    }

    public function onTerminateApp(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestMetrics = $this->metricsFactory->create();

        $logLevel = match (true) {
            $response->getStatusCode() >= 200 && $response->getStatusCode() < 400 => Level::Info,
            $response->getStatusCode() >= 400 && $response->getStatusCode() < 500 => Level::Warning,
            $response->getStatusCode() >= 500 => Level::Error,
            default => Level::Notice,
        };

        $this->logger->log($logLevel, 'HTTP Request', [
            'method' => $request->getMethod(),
            'route' => $request->attributes->get('_route', 'unknown'),
            'uri' => $request->getRequestUri(),
            'ip' => $request->getClientIp(),
            'status_code' => $response->getStatusCode(),
            ...$requestMetrics->getContext(),
        ]);
    }
}
