<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

date_default_timezone_set('UTC');

try {
    require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

    return static function (array $context) {
        return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    };
} catch (\Throwable $throwable) {
    $code = $throwable instanceof HttpExceptionInterface
        ? $throwable->getStatusCode()
        : Response::HTTP_INTERNAL_SERVER_ERROR;

    return new Response('OK', Response::HTTP_OK);
}
