<?php

declare(strict_types=1);

namespace App\Infrastructure\Logger\Formatter;

use App\Infrastructure\Metrics\RequestMetrics;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JsonFormatter implements FormatterInterface
{
    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly RequestMetrics $requestMetrics
    ) {
    }

    public function format(LogRecord $record): string
    {
        $params = $record->toArray();
        $timestamp = "[{$params['datetime']->format('Y-m-d H:i:s')}]";
        $channel = "{$params['channel']}.{$params['level_name']}:";
        $message = str_replace("\n", ' ', $params['message']);
        $context = $this->formatContext($params['context']);
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $message = str_replace("{{$key}}", (string) $value, $message);
            }
        }
        $contextStr = json_encode($context, JSON_THROW_ON_ERROR);

        return "$timestamp $channel $message $contextStr" . PHP_EOL;
    }

    /**
     * @param array<int, LogRecord> $records
     * @return array<int, string>
     */
    public function formatBatch(array $records): array
    {
        /** @var array<int, string> $formatted */
        $formatted = [];
        foreach ($records as $key => $record) {
            $formatted[$key] = $this->format($record);
        }

        return $formatted;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
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
