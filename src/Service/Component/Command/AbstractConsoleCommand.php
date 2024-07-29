<?php

declare(strict_types=1);

namespace App\Service\Component\Command;

use App\Service\UseCase\UseCaseHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;

abstract class AbstractConsoleCommand extends Command
{
    public function __construct(
        protected LoggerInterface $logger,
        protected CacheInterface $cache,
        protected EntityManagerInterface $entityManager,
        protected UseCaseHandler $useCaseHandler,
        protected ParameterBagInterface $parameters
    ) {
        parent::__construct();
    }
}
