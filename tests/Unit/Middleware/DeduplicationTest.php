<?php

declare(strict_types=1);

namespace App\Tests\Unit\Middleware;

use App\Application\Command\Telegram\HelpCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Exception\DuplicateUpdateException;
use App\Infrastructure\Telegram\Middleware\DeduplicationMiddleware;
use App\Tests\Factory\EntityFactory;
use App\Tests\Factory\TelegramUpdateFactory;
use App\Tests\TestCase\AbstractUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;

final class DeduplicationTest extends AbstractUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TelegramUpdateFactory::resetCounter();
    }

    public function testFirstUpdatePassesThrough(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $middleware = new DeduplicationMiddleware($cache, new NullLogger());
        $command = $this->createCommand();

        $cacheItem = $this->createStub(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);

        $cache->method('getItem')->willReturn($cacheItem);
        $cache->expects(self::once())->method('save')->with($cacheItem);

        $result = $middleware->handle($command);

        self::assertSame($command, $result);
    }

    public function testDuplicateUpdateThrowsException(): void
    {
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $middleware = new DeduplicationMiddleware($cache, new NullLogger());
        $command = $this->createCommand();

        $cacheItem = $this->createStub(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);

        $cache->method('getItem')->willReturn($cacheItem);

        $this->expectException(DuplicateUpdateException::class);

        $middleware->handle($command);
    }

    public function testCacheUnavailablePassesThrough(): void
    {
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $middleware = new DeduplicationMiddleware($cache, new NullLogger());
        $command = $this->createCommand();

        $cache->method('getItem')->willThrowException(new \RuntimeException('Cache down'));

        $result = $middleware->handle($command);

        self::assertSame($command, $result);
    }

    public function testDifferentUpdateIdsBothPassThrough(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $middleware = new DeduplicationMiddleware($cache, new NullLogger());

        $commandA = $this->createCommand(100);
        $commandB = $this->createCommand(200);

        $cacheItemA = $this->createStub(CacheItemInterface::class);
        $cacheItemA->method('isHit')->willReturn(false);

        $cacheItemB = $this->createStub(CacheItemInterface::class);
        $cacheItemB->method('isHit')->willReturn(false);

        $cache->method('getItem')->willReturnCallback(
            static function (string $key) use ($cacheItemA, $cacheItemB): CacheItemInterface {
                if (str_contains($key, '100')) {
                    return $cacheItemA;
                }
                return $cacheItemB;
            }
        );

        $cache->expects(self::exactly(2))->method('save');

        $resultA = $middleware->handle($commandA);
        $resultB = $middleware->handle($commandB);

        self::assertSame($commandA, $resultA);
        self::assertSame($commandB, $resultB);
    }

    private function createCommand(int $updateId = 1): TelegramCommandInterface
    {
        $update = TelegramUpdateFactory::createTextMessage(-1001234567890, 12345, '/help');
        $update->update_id = $updateId;

        $chat = EntityFactory::createChat(-1001234567890);
        $user = EntityFactory::createUser(-1001234567890, 12345);

        return new HelpCommand($update, $chat, $user);
    }
}
