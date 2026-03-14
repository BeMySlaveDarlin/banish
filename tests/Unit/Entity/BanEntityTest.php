<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Exception\InvalidBanStateTransitionException;
use App\Tests\Factory\EntityFactory;
use App\Tests\TestCase\AbstractUnitTestCase;

final class BanEntityTest extends AbstractUnitTestCase
{
    public function testMarkAsBannedFromPendingSucceeds(): void
    {
        $ban = EntityFactory::createBan(-1001180970364, 999, 111);

        $ban->markAsBanned();

        self::assertSame(BanStatus::BANNED, $ban->getStatus());
    }

    public function testMarkAsBannedFromBannedThrows(): void
    {
        $ban = EntityFactory::createBan(-1001180970364, 999, 111, BanStatus::BANNED);

        $this->expectException(InvalidBanStateTransitionException::class);

        $ban->markAsBanned();
    }

    public function testMarkAsBannedFromCanceledThrows(): void
    {
        $ban = EntityFactory::createBan(-1001180970364, 999, 111, BanStatus::CANCELED);

        $this->expectException(InvalidBanStateTransitionException::class);

        $ban->markAsBanned();
    }

    public function testMarkAsForgivenFromPendingSucceeds(): void
    {
        $ban = EntityFactory::createBan(-1001180970364, 999, 111);

        $ban->markAsForgiven();

        self::assertSame(BanStatus::CANCELED, $ban->getStatus());
    }

    public function testMarkAsForgivenFromBannedThrows(): void
    {
        $ban = EntityFactory::createBan(-1001180970364, 999, 111, BanStatus::BANNED);

        $this->expectException(InvalidBanStateTransitionException::class);

        $ban->markAsForgiven();
    }

    public function testMarkAsExpiredFromPendingSucceeds(): void
    {
        $ban = EntityFactory::createBan(-1001180970364, 999, 111);

        $ban->markAsExpired();

        self::assertSame(BanStatus::DELETED, $ban->getStatus());
    }

    public function testMarkAsCleanedUpFromBannedSucceeds(): void
    {
        $ban = EntityFactory::createBan(-1001180970364, 999, 111, BanStatus::BANNED);

        $ban->markAsCleanedUp();

        self::assertSame(BanStatus::DELETED, $ban->getStatus());
    }

    public function testMarkAsCleanedUpFromDeletedThrows(): void
    {
        $ban = EntityFactory::createBan(-1001180970364, 999, 111, BanStatus::DELETED);

        $this->expectException(InvalidBanStateTransitionException::class);

        $ban->markAsCleanedUp();
    }

    public function testCreateFactoryReturnsCorrectDefaults(): void
    {
        $ban = TelegramChatUserBanEntity::create(
            chatId: -1001180970364,
            reporterId: 111,
            spammerId: 999,
            banMessageId: 12345,
        );

        self::assertSame(-1001180970364, $ban->chatId);
        self::assertSame(111, $ban->reporterId);
        self::assertSame(999, $ban->spammerId);
        self::assertSame(12345, $ban->banMessageId);
        self::assertNull($ban->spamMessageId);
        self::assertNull($ban->initialMessageId);
        self::assertSame(BanStatus::PENDING, $ban->getStatus());
    }

    public function testIsPendingIsBannedIsCanceledWork(): void
    {
        $pending = EntityFactory::createBan(-1001180970364, 999, 111);
        self::assertTrue($pending->isPending());
        self::assertFalse($pending->isBanned());
        self::assertFalse($pending->isCanceled());

        $banned = EntityFactory::createBan(-1001180970364, 999, 111, BanStatus::BANNED);
        self::assertFalse($banned->isPending());
        self::assertTrue($banned->isBanned());
        self::assertFalse($banned->isCanceled());

        $canceled = EntityFactory::createBan(-1001180970364, 999, 111, BanStatus::CANCELED);
        self::assertFalse($canceled->isPending());
        self::assertFalse($canceled->isBanned());
        self::assertTrue($canceled->isCanceled());
    }
}
