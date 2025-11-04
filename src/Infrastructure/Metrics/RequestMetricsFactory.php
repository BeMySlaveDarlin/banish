<?php

declare(strict_types=1);

namespace App\Infrastructure\Metrics;

class RequestMetricsFactory
{
    public function create(): RequestMetrics
    {
        return new RequestMetrics();
    }
}
