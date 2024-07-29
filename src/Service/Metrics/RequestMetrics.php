<?php

declare(strict_types=1);

namespace App\Service\Metrics;

use Ramsey\Uuid\Uuid;

class RequestMetrics
{
    public const string START_TIME = 'rts';
    public const string FINISH_TIME = 'rtf';
    public const string EXECUTION_TIME = 'rte';

    private array $data = [];
    private array $context = [];

    public function __construct()
    {
        $this->data[self::START_TIME] = microtime(true);
        $this->context['request_id'] = Uuid::uuid4()->toString();
    }

    public function setRequestUri(string $uri): void
    {
        $this->context['uri'] = $uri;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getMetrics(): array
    {
        $this->data[self::FINISH_TIME] = microtime(true);
        $this->data[self::EXECUTION_TIME] = $this->data[self::FINISH_TIME] - ($this->data[self::START_TIME] ?? $this->data[self::FINISH_TIME]);

        return $this->data;
    }
}
