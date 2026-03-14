<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Middleware;

use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Exception\DuplicateUpdateException;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class DeduplicationMiddleware implements MiddlewareInterface
{
    private const int TTL_SECONDS = 60;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(TelegramCommandInterface $command): TelegramCommandInterface
    {
        /** @var TelegramUpdate|null $update */
        $update = $command->update ?? null;
        if (!$update instanceof TelegramUpdate) {
            return $command;
        }

        $updateId = $update->update_id;
        $cacheKey = "dedup_update_$updateId";

        try {
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                throw new DuplicateUpdateException($updateId);
            }

            $item->set(true);
            $item->expiresAfter(self::TTL_SECONDS);
            $this->cache->save($item);
        } catch (DuplicateUpdateException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->warning('Deduplication cache unavailable, passing through', [
                'updateId' => $updateId,
                'error' => $e->getMessage(),
            ]);
        }

        return $command;
    }
}
