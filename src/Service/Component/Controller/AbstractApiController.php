<?php

declare(strict_types=1);

namespace App\Service\Component\Controller;

use App\Service\Metrics\RequestMetrics;
use App\Service\UseCase\UseCaseHandler;
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
        protected UseCaseHandler $useCaseHandler,
        protected RequestMetrics $requestMetrics
    ) {
    }
}
