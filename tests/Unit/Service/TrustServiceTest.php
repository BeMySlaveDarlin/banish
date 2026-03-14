<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\TrustService;
use App\Domain\Common\ValueObject\JsonBValue;
use App\Tests\TestCase\AbstractUnitTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;

final class TrustServiceTest extends AbstractUnitTestCase
{
    private RequestHistoryRepository&Stub $requestHistoryRepository;
    private ChatConfigServiceInterface&Stub $chatConfigService;
    private UserRepository&Stub $userRepository;
    private TrustService $trustService;

    protected function setUp(): void
    {
        $this->requestHistoryRepository = $this->createStub(RequestHistoryRepository::class);
        $this->chatConfigService = $this->createStub(ChatConfigServiceInterface::class);
        $this->userRepository = $this->createStub(UserRepository::class);

        $this->trustService = new TrustService(
            $this->requestHistoryRepository,
            $this->chatConfigService,
            $this->userRepository,
        );
    }

    public function testIsUserTrustedWhenInChatForWeek(): void
    {
        $chat = $this->createChat();
        $user = $this->createUser();
        $user->createdAt = new DateTimeImmutable('-8 days');

        $this->userRepository->method('findByChatAndUser')->willReturn($user);

        $result = $this->trustService->isUserTrusted($chat, $user->userId);

        self::assertTrue($result);
    }

    public function testIsUserTrustedWhenEnoughMessages(): void
    {
        $chat = $this->createChat();
        $user = $this->createUser();
        $user->createdAt = new DateTimeImmutable('-1 day');

        $this->userRepository->method('findByChatAndUser')->willReturn($user);
        $this->chatConfigService->method('getMinMessagesForTrust')->willReturn(5);
        $this->requestHistoryRepository->method('countMessagesByFromId')->willReturn(10);

        $result = $this->trustService->isUserTrusted($chat, $user->userId);

        self::assertTrue($result);
    }

    public function testIsUserNotTrustedWhenNewAndFewMessages(): void
    {
        $chat = $this->createChat();
        $user = $this->createUser();
        $user->createdAt = new DateTimeImmutable('-1 day');

        $this->userRepository->method('findByChatAndUser')->willReturn($user);
        $this->chatConfigService->method('getMinMessagesForTrust')->willReturn(5);
        $this->requestHistoryRepository->method('countMessagesByFromId')->willReturn(2);

        $result = $this->trustService->isUserTrusted($chat, $user->userId);

        self::assertFalse($result);
    }

    public function testIsUserNotTrustedWhenUserNotFound(): void
    {
        $chat = $this->createChat();
        $userId = 999999;

        $this->userRepository->method('findByChatAndUser')->willReturn(null);
        $this->chatConfigService->method('getMinMessagesForTrust')->willReturn(5);
        $this->requestHistoryRepository->method('countMessagesByFromId')->willReturn(0);

        $result = $this->trustService->isUserTrusted($chat, $userId);

        self::assertFalse($result);
    }

    public function testIsUserTrustedExactlySevenDays(): void
    {
        $chat = $this->createChat();
        $user = $this->createUser();
        $user->createdAt = new DateTimeImmutable('-7 days');

        $this->userRepository->method('findByChatAndUser')->willReturn($user);

        $result = $this->trustService->isUserTrusted($chat, $user->userId);

        self::assertTrue($result);
    }

    public function testIsUserTrustedExactlyAtMinMessages(): void
    {
        $chat = $this->createChat();
        $user = $this->createUser();
        $user->createdAt = new DateTimeImmutable('-1 day');

        $this->userRepository->method('findByChatAndUser')->willReturn($user);
        $this->chatConfigService->method('getMinMessagesForTrust')->willReturn(5);
        $this->requestHistoryRepository->method('countMessagesByFromId')->willReturn(5);

        $result = $this->trustService->isUserTrusted($chat, $user->userId);

        self::assertTrue($result);
    }

    private function createChat(int $chatId = -1001180970364): TelegramChatEntity
    {
        $chat = new TelegramChatEntity();
        $chat->chatId = $chatId;
        $chat->type = 'supergroup';
        $chat->name = 'Test Chat';
        $chat->isEnabled = true;
        $chat->options = new JsonBValue(TelegramChatEntity::getDefaultOptions());

        return $chat;
    }

    private function createUser(int $userId = 331523702): TelegramChatUserEntity
    {
        $user = new TelegramChatUserEntity();
        $user->chatId = -1001180970364;
        $user->userId = $userId;
        $user->username = 'testuser';
        $user->name = 'Test User';
        $user->isAdmin = false;
        $user->isBot = false;
        $user->createdAt = new DateTimeImmutable();

        return $user;
    }
}
