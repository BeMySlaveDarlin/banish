<?php

declare(strict_types=1);

namespace App\Service\Logger;

trait FormatContextTrait
{
    public function formatContext(array $context): array
    {
        $metricsTimestamps = $this->requestMetrics->getMetrics();
        $metricsContext = $this->requestMetrics->getContext();

        $context['app'] = $this->params->get('app.name');
        $context['env'] = $this->params->get('app.env');

        $context['rid'] = $metricsContext['request_id'] ?? null;
        $context['uri'] = $context['uri'] ?? $metricsContext['uri'] ?? null;
        $context['metrics'] = $context['metrics'] ?? $metricsTimestamps;

        return $context;
    }
}
