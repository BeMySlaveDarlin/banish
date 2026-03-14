<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Common\ValueObject\JsonBValue;
use App\Domain\Telegram\Entity\TelegramRequestHistoryEntity;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\SpammerMessageService;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageEntity;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Tests\Factory\EntityFactory;
use App\Tests\TestCase\AbstractUnitTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;

final class SpammerMessageServiceTest extends AbstractUnitTestCase
{
    /** @var UserRepository&Stub */
    private UserRepository $userRepository;
    /** @var RequestHistoryRepository&Stub */
    private RequestHistoryRepository $requestHistoryRepository;
    private SpammerMessageService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->requestHistoryRepository = $this->createStub(RequestHistoryRepository::class);

        $this->service = new SpammerMessageService(
            $this->userRepository,
            $this->requestHistoryRepository,
            'BanishBot',
        );
    }

    public function testGetSpammerMessageViaReply(): void
    {
        $replyMessage = new TelegramMessage();
        $replyMessage->message_id = 100;
        $replyMessage->date = time();
        $replyMessage->chat = $this->createChat(-1001180970364);
        $replyMessage->from = $this->createFrom(999);

        $message = new TelegramMessage();
        $message->message_id = 200;
        $message->date = time();
        $message->text = '/ban';
        $message->chat = $this->createChat(-1001180970364);
        $message->from = $this->createFrom(111);
        $message->reply_to_message = $replyMessage;

        $update = new TelegramUpdate();
        $update->update_id = 1;
        $update->message = $message;

        $result = $this->service->getSpammerMessage($update);

        self::assertNotNull($result);
        self::assertSame(100, $result->message_id);
        self::assertSame(999, $result->from->id);
    }

    public function testGetSpammerMessageViaMention(): void
    {
        $entity = new TelegramMessageEntity();
        $entity->type = 'text_mention';
        $entity->offset = 5;
        $entity->length = 13;
        $entity->user = ['id' => 999];

        $message = new TelegramMessage();
        $message->message_id = 200;
        $message->date = time();
        $message->text = '/ban @spammer_user BanishBot';
        $message->chat = $this->createChat(-1001180970364);
        $message->from = $this->createFrom(111);
        $message->entities = [$entity];

        $update = new TelegramUpdate();
        $update->update_id = 1;
        $update->message = $message;

        $user = EntityFactory::createUser(-1001180970364, 999);
        $user->username = 'spammer_user';
        $user->name = 'Spammer';

        $this->userRepository
            ->method('findByChatAndUsername')
            ->willReturn($user);

        $result = $this->service->getSpammerMessage($update);

        self::assertNotNull($result);
        self::assertSame(999, $result->from->id);
    }

    public function testGetSpammerMessageViaPreviousMessageWithinAge(): void
    {
        $message = new TelegramMessage();
        $message->message_id = 200;
        $message->date = time();
        $message->text = '/ban';
        $message->chat = $this->createChat(-1001180970364);
        $message->from = $this->createFrom(111);

        $update = new TelegramUpdate();
        $update->update_id = 1;
        $update->message = $message;

        $history = new TelegramRequestHistoryEntity();
        $history->chatId = -1001180970364;
        $history->fromId = 111;
        $history->messageId = 199;
        $history->updateId = 0;
        $history->createdAt = new DateTimeImmutable('-10 seconds');
        $history->request = new JsonBValue([
            'message' => [
                'message_id' => 199,
                'from' => ['id' => 888, 'is_bot' => false, 'first_name' => 'Prev'],
                'chat' => ['id' => -1001180970364, 'type' => 'supergroup'],
                'date' => time() - 10,
                'text' => 'spam text',
            ],
        ]);

        $this->requestHistoryRepository
            ->method('findPreviousMessage')
            ->willReturn($history);

        $result = $this->service->getSpammerMessage($update);

        self::assertNotNull($result);
        self::assertSame(199, $result->message_id);
        self::assertSame(888, $result->from->id);
    }

    public function testGetSpammerMessageViaPreviousMessageTooOldReturnsNull(): void
    {
        $message = new TelegramMessage();
        $message->message_id = 200;
        $message->date = time();
        $message->text = '/ban';
        $message->chat = $this->createChat(-1001180970364);
        $message->from = $this->createFrom(111);

        $update = new TelegramUpdate();
        $update->update_id = 1;
        $update->message = $message;

        $history = new TelegramRequestHistoryEntity();
        $history->chatId = -1001180970364;
        $history->fromId = 111;
        $history->messageId = 199;
        $history->updateId = 0;
        $history->createdAt = new DateTimeImmutable('-60 seconds');
        $history->request = new JsonBValue([
            'message' => [
                'message_id' => 199,
                'from' => ['id' => 888, 'is_bot' => false],
                'chat' => ['id' => -1001180970364, 'type' => 'supergroup'],
                'date' => time() - 60,
                'text' => 'old spam',
            ],
        ]);

        $this->requestHistoryRepository
            ->method('findPreviousMessage')
            ->willReturn($history);

        $result = $this->service->getSpammerMessage($update);

        self::assertNull($result);
    }

    public function testGetSpammerMessageNoPreviousMessageReturnsNull(): void
    {
        $message = new TelegramMessage();
        $message->message_id = 200;
        $message->date = time();
        $message->text = '/ban';
        $message->chat = $this->createChat(-1001180970364);
        $message->from = $this->createFrom(111);

        $update = new TelegramUpdate();
        $update->update_id = 1;
        $update->message = $message;

        $this->requestHistoryRepository
            ->method('findPreviousMessage')
            ->willReturn(null);

        $result = $this->service->getSpammerMessage($update);

        self::assertNull($result);
    }

    public function testGetSpammerMessageMentionWithUnknownUserReturnsNull(): void
    {
        $entity = new TelegramMessageEntity();
        $entity->type = 'text_mention';
        $entity->offset = 5;
        $entity->length = 13;
        $entity->user = ['id' => 777];

        $message = new TelegramMessage();
        $message->message_id = 200;
        $message->date = time();
        $message->text = '/ban @unknown_user BanishBot';
        $message->chat = $this->createChat(-1001180970364);
        $message->from = $this->createFrom(111);
        $message->entities = [$entity];

        $update = new TelegramUpdate();
        $update->update_id = 1;
        $update->message = $message;

        $this->userRepository
            ->method('findByChatAndUsername')
            ->willReturn(null);

        $result = $this->service->getSpammerMessage($update);

        self::assertNull($result);
    }

    private function createChat(int $chatId): TelegramMessageChat
    {
        $chat = new TelegramMessageChat();
        $chat->id = $chatId;
        $chat->type = 'supergroup';

        return $chat;
    }

    private function createFrom(int $userId): TelegramMessageFrom
    {
        $from = new TelegramMessageFrom();
        $from->id = $userId;
        $from->is_bot = false;
        $from->first_name = 'User';
        $from->username = 'user_' . $userId;

        return $from;
    }
}
