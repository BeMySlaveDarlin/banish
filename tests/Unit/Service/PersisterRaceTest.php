<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Service\ChatPersisterInterface;
use App\Domain\Telegram\Service\UserPersisterInterface;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;
use App\Tests\Factory\EntityFactory;
use App\Tests\TestCase\AbstractUnitTestCase;

final class PersisterRaceTest extends AbstractUnitTestCase
{
    public function testUserPersisterCreateReturnsUserWithCorrectIds(): void
    {
        $expectedUser = EntityFactory::createUser(-1001234567890, 12345);

        $persister = $this->createMock(UserPersisterInterface::class);
        $persister->expects(self::once())
            ->method('persist')
            ->with(
                self::callback(static fn (TelegramMessageChat $c): bool => $c->id === -1001234567890),
                self::callback(static fn (TelegramMessageFrom $f): bool => $f->id === 12345),
            )
            ->willReturn($expectedUser);

        $result = $persister->persist(
            $this->createTelegramChat(-1001234567890),
            $this->createTelegramFrom(12345),
        );

        self::assertSame(-1001234567890, $result->chatId);
        self::assertSame(12345, $result->userId);
    }

    public function testUserPersisterRaceConditionAlwaysReturnsSameEntity(): void
    {
        $existingUser = EntityFactory::createUser(-1001234567890, 12345);
        $existingUser->name = 'Persisted User';

        $persister = $this->createStub(UserPersisterInterface::class);
        $persister->method('persist')->willReturn($existingUser);

        $chat = $this->createTelegramChat(-1001234567890);
        $from = $this->createTelegramFrom(12345);

        $first = $persister->persist($chat, $from);
        $second = $persister->persist($chat, $from);

        self::assertSame($first, $second);
        self::assertSame('Persisted User', $first->name);
    }

    public function testChatPersisterRaceConditionAlwaysReturnsSameEntity(): void
    {
        $existingChat = EntityFactory::createChat(-1001234567890, ['name' => 'Persisted Chat']);

        $persister = $this->createStub(ChatPersisterInterface::class);
        $persister->method('persist')->willReturn($existingChat);

        $tgChat = new TelegramMessageChat();
        $tgChat->id = -1001234567890;
        $tgChat->type = 'supergroup';

        $first = $persister->persist($tgChat);
        $second = $persister->persist($tgChat);

        self::assertSame($first, $second);
        self::assertSame('Persisted Chat', $first->name);
        self::assertSame(-1001234567890, $first->chatId);
    }

    private function createTelegramChat(int $chatId): TelegramMessageChat
    {
        $chat = new TelegramMessageChat();
        $chat->id = $chatId;
        $chat->type = 'supergroup';

        return $chat;
    }

    private function createTelegramFrom(int $userId): TelegramMessageFrom
    {
        $from = new TelegramMessageFrom();
        $from->id = $userId;
        $from->first_name = 'Test';
        $from->last_name = 'User';
        $from->username = 'testuser';
        $from->is_bot = false;

        return $from;
    }
}
