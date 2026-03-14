<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Telegram\Constants\Emoji;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Service\BanMessageFormatter;
use App\Tests\TestCase\AbstractUnitTestCase;

final class BanMessageFormatterTest extends AbstractUnitTestCase
{
    private BanMessageFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new BanMessageFormatter();
    }

    public function testFormatStartBanMessageWithUsernames(): void
    {
        $reporter = $this->createUser(1, 'reporter_user');
        $spammer = $this->createUser(2, 'spammer_user');

        $result = $this->formatter->formatStartBanMessage($reporter, $spammer);

        self::assertStringContainsString('@reporter_user', $result);
        self::assertStringContainsString('@spammer_user', $result);
        self::assertStringContainsString('requested ban procedure on spammer', $result);
    }

    public function testFormatStartBanMessageWithNamesOnly(): void
    {
        $reporter = $this->createUser(1, null, 'John');
        $spammer = $this->createUser(2, null, 'Spam Guy');

        $result = $this->formatter->formatStartBanMessage($reporter, $spammer);

        self::assertStringContainsString('John', $result);
        self::assertStringContainsString('Spam Guy', $result);
    }

    public function testFormatInitialVoteMessageBan(): void
    {
        $reporter = $this->createUser(1, 'voter_user');

        $result = $this->formatter->formatInitialVoteMessage($reporter, VoteType::BAN);

        self::assertStringContainsString('@voter_user', $result);
        self::assertStringContainsString(VoteType::BAN->value, $result);
        self::assertStringContainsString(Emoji::BAN, $result);
    }

    public function testFormatInitialVoteMessageForgive(): void
    {
        $reporter = $this->createUser(1, 'voter_user');

        $result = $this->formatter->formatInitialVoteMessage($reporter, VoteType::FORGIVE);

        self::assertStringContainsString('@voter_user', $result);
        self::assertStringContainsString(VoteType::FORGIVE->value, $result);
        self::assertStringContainsString(Emoji::FORGIVE, $result);
    }

    public function testFormatVoteMessagePending(): void
    {
        $ban = $this->createBan(BanStatus::PENDING);
        $reporter = $this->createUser(1, 'reporter');
        $spammer = $this->createUser(2, 'spammer');

        $upVoters = [$this->createUser(3, 'voter1')];
        $downVoters = [];

        $result = $this->formatter->formatVoteMessage(
            $ban,
            $reporter,
            $spammer,
            $upVoters,
            $downVoters,
        );

        self::assertStringContainsString('@reporter', $result);
        self::assertStringContainsString('@spammer', $result);
        self::assertStringContainsString('@voter1', $result);
        self::assertStringNotContainsString('is banned', $result);
        self::assertStringNotContainsString('is not banned', $result);
    }

    public function testFormatVoteMessageBanned(): void
    {
        $ban = $this->createBan(BanStatus::BANNED);
        $reporter = $this->createUser(1, 'reporter');
        $spammer = $this->createUser(2, 'spammer');

        $result = $this->formatter->formatVoteMessage(
            $ban,
            $reporter,
            $spammer,
            [],
            [],
        );

        self::assertStringContainsString('@spammer is banned', $result);
    }

    public function testFormatVoteMessageCanceled(): void
    {
        $ban = $this->createBan(BanStatus::CANCELED);
        $reporter = $this->createUser(1, 'reporter');
        $spammer = $this->createUser(2, 'spammer');

        $result = $this->formatter->formatVoteMessage(
            $ban,
            $reporter,
            $spammer,
            [],
            [],
        );

        self::assertStringContainsString('@spammer is not banned', $result);
    }

    public function testFormatVoteMessageDeleteOnly(): void
    {
        $ban = $this->createBan(BanStatus::CANCELED);
        $reporter = $this->createUser(1, 'reporter');
        $spammer = $this->createUser(2, 'spammer');

        $result = $this->formatter->formatVoteMessage(
            $ban,
            $reporter,
            $spammer,
            [],
            [],
            deleteOnlyMessage: true,
        );

        self::assertStringContainsString('message is deleted', $result);
    }

    public function testFormatVoteMessageWithNullUsers(): void
    {
        $ban = $this->createBan(BanStatus::BANNED);

        $result = $this->formatter->formatVoteMessage(
            $ban,
            null,
            null,
            [],
            [],
        );

        self::assertStringContainsString('Unknown', $result);
    }

    public function testFormatVoteButtonTextBan(): void
    {
        $result = $this->formatter->formatVoteButtonText(2, 3, VoteType::BAN);

        self::assertSame(Emoji::BAN . ' Ban (2/3)', $result);
    }

    public function testFormatVoteButtonTextForgive(): void
    {
        $result = $this->formatter->formatVoteButtonText(1, 3, VoteType::FORGIVE);

        self::assertSame(Emoji::FORGIVE . ' Forgive (1/3)', $result);
    }

    public function testFormatVoteButtonTextZeroVotes(): void
    {
        $result = $this->formatter->formatVoteButtonText(0, 5, VoteType::BAN);

        self::assertSame(Emoji::BAN . ' Ban (0/5)', $result);
    }

    private function createUser(int $userId, ?string $username = null, ?string $name = null): TelegramChatUserEntity
    {
        $user = new TelegramChatUserEntity();
        $user->chatId = -1001180970364;
        $user->userId = $userId;
        $user->username = $username;
        $user->name = $name;
        $user->isAdmin = false;
        $user->isBot = false;

        return $user;
    }

    private function createBan(BanStatus $status = BanStatus::PENDING): TelegramChatUserBanEntity
    {
        $ban = TelegramChatUserBanEntity::create(
            -1001180970364,
            217708876,
            7816394199,
            12345
        );

        match ($status) {
            BanStatus::BANNED => $ban->markAsBanned(),
            BanStatus::CANCELED => $ban->markAsForgiven(),
            BanStatus::DELETED => $ban->markAsExpired(),
            BanStatus::PENDING => null,
        };

        return $ban;
    }
}
