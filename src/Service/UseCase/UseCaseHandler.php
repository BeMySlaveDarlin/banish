<?php

namespace App\Service\UseCase;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Throwable;

class UseCaseHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws OptimisticLockException|Throwable|ORMException|Exception
     */
    public function handle(UseCaseInterface $useCase): mixed
    {
        if ($useCase instanceof NonTransactionalUseCaseInterface) {
            return $useCase->execute();
        }

        $this->entityManager->beginTransaction();

        try {
            $result = $useCase->execute();

            $this->entityManager->commit();

            return $result;
        } catch (Throwable $e) {
            $this->entityManager->rollBack();

            $this->logger->error($e->getMessage(), $e->getTrace());

            throw $e;
        }
    }
}
