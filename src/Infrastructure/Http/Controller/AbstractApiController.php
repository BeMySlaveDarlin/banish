<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Metrics\RequestMetrics;
use App\Infrastructure\Metrics\RequestMetricsFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;

abstract class AbstractApiController extends AbstractController
{
    protected RequestMetrics $requestMetrics;

    public function __construct(
        protected LoggerInterface $logger,
        protected CacheInterface $cache,
        protected EntityManagerInterface $entityManager,
        RequestMetricsFactory $metricsFactory,
        private readonly RequestStack $requestStack
    ) {
        $this->requestMetrics = $metricsFactory->create();
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $request->attributes->set('request_metrics', $this->requestMetrics);
        }
    }
}
