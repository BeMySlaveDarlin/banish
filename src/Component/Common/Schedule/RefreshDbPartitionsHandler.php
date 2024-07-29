<?php

declare(strict_types=1);

namespace App\Component\Common\Schedule;

use App\Component\Common\UseCase\RefreshDbPartitionsUseCase;
use App\Service\UseCase\UseCaseHandler;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RefreshDbPartitionsHandler
{
    public function __construct(
        private Connection $connection,
        private UseCaseHandler $useCaseHandler,
        private ParameterBagInterface $parameters
    ) {
    }

    public function __invoke(RefreshDbPartitionsMessage $message): void
    {
        $this->useCaseHandler->handle(
            new RefreshDbPartitionsUseCase($this->connection, $this->parameters)
        );
    }
}
