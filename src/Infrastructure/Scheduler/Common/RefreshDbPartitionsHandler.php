<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler\Common;

use App\Domain\Common\Service\PartitionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RefreshDbPartitionsHandler
{
    public function __construct(
        private readonly PartitionService $partitionService
    ) {
    }

    public function __invoke(RefreshDbPartitionsMessage $message): void
    {
        $this->partitionService->refreshPartitions();
    }
}
