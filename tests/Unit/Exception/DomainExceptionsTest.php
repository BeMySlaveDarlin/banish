<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Exception\DuplicateUpdateException;
use App\Domain\Telegram\Exception\InvalidBanStateTransitionException;
use App\Domain\Telegram\Exception\TelegramApiException;
use App\Tests\TestCase\AbstractUnitTestCase;

final class DomainExceptionsTest extends AbstractUnitTestCase
{
    public function testInvalidBanStateTransitionExceptionHasCorrectMessage(): void
    {
        $exception = InvalidBanStateTransitionException::create(BanStatus::BANNED, BanStatus::PENDING);

        self::assertSame(
            'Invalid ban state transition from "banned" to "pending"',
            $exception->getMessage(),
        );
        self::assertInstanceOf(\DomainException::class, $exception);
    }

    public function testTelegramApiExceptionFromThrowable(): void
    {
        $previous = new \RuntimeException('Connection refused', 500);
        $exception = TelegramApiException::fromThrowable('banChatMember', $previous);

        self::assertSame(
            'Telegram API error during "banChatMember": Connection refused',
            $exception->getMessage(),
        );
        self::assertSame(500, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testTelegramApiExceptionRequestFailed(): void
    {
        $exception = TelegramApiException::requestFailed('sendMessage', 'chat not found');

        self::assertSame(
            'Telegram API request "sendMessage" failed: chat not found',
            $exception->getMessage(),
        );
        self::assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testDuplicateUpdateExceptionHasCorrectMessage(): void
    {
        $exception = new DuplicateUpdateException(12345);

        self::assertSame('Duplicate update: 12345', $exception->getMessage());
        self::assertInstanceOf(\RuntimeException::class, $exception);
    }
}
