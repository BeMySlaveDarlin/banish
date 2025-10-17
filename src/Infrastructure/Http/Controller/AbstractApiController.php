<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Metrics\RequestMetrics;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\CacheInterface;

abstract class AbstractApiController extends AbstractController
{
    public function __construct(
        protected LoggerInterface $logger,
        protected CacheInterface $cache,
        protected EntityManagerInterface $entityManager,
        protected RequestMetrics $requestMetrics
    ) {
    }
}
