<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\Service\BanService;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\TelegramChatMemberApiInterface;
use App\Domain\Telegram\Service\TelegramMessageApiInterface;
use App\Domain\Common\ValueObject\JsonBValue;
use App\Tests\TestCase\AbstractUnitTestCase;
use Psr\Log\NullLogger;

final class BanServiceTest extends AbstractUnitTestCase
{
    public function testBanUserWhenDeleteOnlyDisabled(): void
    {
        $configStub = $this->createStub(ChatConfigServiceInterface::class);
        $configStub->method('isDeleteOnlyEnabled')->willReturn(false);
        $configStub->method('isDeleteMessagesEnabled')->willReturn(false);

        $banRepo = $this->createMock(BanRepository::class);
        $chatMemberApi = $this->createMock(TelegramChatMemberApiInterface::class);
        $messageApi = $this->createStub(TelegramMessageApiInterface::class);

        $chat = $this->createChat();
        $ban = $this->createBan();

        $chatMemberApi
            ->expects(self::once())
            ->method('banChatMember')
            ->with($chat->chatId, $ban->spammerId)
            ->willReturn(true);

        $banRepo->expects(self::once())->method('save')->with($ban);

        $service = new BanService(
            $banRepo,
            $this->createStub(VoteRepository::class),
            $configStub,
            $chatMemberApi,
            $messageApi,
            $this->createStub(RequestHistoryRepository::class),
            new NullLogger(),
        );

        $service->banUser($chat, $ban);

        self::assertSame(BanStatus::BANNED, $ban->getStatus());
    }

    public function testBanUserWhenDeleteOnlyEnabled(): void
    {
        $configStub = $this->createStub(ChatConfigServiceInterface::class);
        $configStub->method('isDeleteOnlyEnabled')->willReturn(true);
        $configStub->method('isDeleteMessagesEnabled')->willReturn(false);

        $banRepo = $this->createMock(BanRepository::class);
        $chatMemberApi = $this->createMock(TelegramChatMemberApiInterface::class);
        $messageApi = $this->createStub(TelegramMessageApiInterface::class);

        $chat = $this->createChat();
        $ban = $this->createBan();

        $chatMemberApi->expects(self::never())->method('banChatMember');
        $banRepo->expects(self::once())->method('save')->with($ban);

        $service = new BanService(
            $banRepo,
            $this->createStub(VoteRepository::class),
            $configStub,
            $chatMemberApi,
            $messageApi,
            $this->createStub(RequestHistoryRepository::class),
            new NullLogger(),
        );

        $service->banUser($chat, $ban);

        self::assertSame(BanStatus::CANCELED, $ban->getStatus());
    }

    public function testBanUserDeletesSpammerMessagesWhenEnabled(): void
    {
        $configStub = $this->createStub(ChatConfigServiceInterface::class);
        $configStub->method('isDeleteOnlyEnabled')->willReturn(false);
        $configStub->method('isDeleteMessagesEnabled')->willReturn(true);

        $banRepo = $this->createMock(BanRepository::class);
        $chatMemberApi = $this->createMock(TelegramChatMemberApiInterface::class);
        $messageApi = $this->createMock(TelegramMessageApiInterface::class);
        $historyRepo = $this->createMock(RequestHistoryRepository::class);

        $chat = $this->createChat();
        $ban = $this->createBan();

        $chatMemberApi->expects(self::once())->method('banChatMember')->willReturn(true);
        $historyRepo->expects(self::once())->method('getMessageIdsByFromId')->willReturn([100, 101, 102]);
        $messageApi->expects(self::exactly(3))->method('deleteMessage');
        $banRepo->expects(self::once())->method('save');

        $service = new BanService(
            $banRepo,
            $this->createStub(VoteRepository::class),
            $configStub,
            $chatMemberApi,
            $messageApi,
            $historyRepo,
            new NullLogger(),
        );
        $service->banUser($chat, $ban);
    }

    public function testBanUserDeleteOnlyDeletesSingleMessage(): void
    {
        $configStub = $this->createStub(ChatConfigServiceInterface::class);
        $configStub->method('isDeleteOnlyEnabled')->willReturn(true);
        $configStub->method('isDeleteMessagesEnabled')->willReturn(true);

        $banRepo = $this->createMock(BanRepository::class);
        $chatMemberApi = $this->createStub(TelegramChatMemberApiInterface::class);
        $messageApi = $this->createMock(TelegramMessageApiInterface::class);
        $historyRepo = $this->createMock(RequestHistoryRepository::class);

        $chat = $this->createChat();
        $ban = $this->createBan();
        $ban->spamMessageId = 555;

        $messageApi->expects(self::once())->method('deleteMessage')->with($chat->chatId, 555);
        $historyRepo->expects(self::never())->method('getMessageIdsByFromId');
        $banRepo->expects(self::once())->method('save');

        $service = new BanService(
            $banRepo,
            $this->createStub(VoteRepository::class),
            $configStub,
            $chatMemberApi,
            $messageApi,
            $historyRepo,
            new NullLogger(),
        );
        $service->banUser($chat, $ban);

        self::assertSame(BanStatus::CANCELED, $ban->getStatus());
    }

    public function testForgiveBanSetsCanceledStatus(): void
    {
        $banRepo = $this->createMock(BanRepository::class);
        $ban = $this->createBan();

        $banRepo->expects(self::once())->method('save')->with($ban);

        $service = new BanService(
            $banRepo,
            $this->createStub(VoteRepository::class),
            $this->createStub(ChatConfigServiceInterface::class),
            $this->createStub(TelegramChatMemberApiInterface::class),
            $this->createStub(TelegramMessageApiInterface::class),
            $this->createStub(RequestHistoryRepository::class),
            new NullLogger(),
        );

        $service->forgiveBan($ban);

        self::assertSame(BanStatus::CANCELED, $ban->getStatus());
    }

    public function testForgiveBanFromPendingStatus(): void
    {
        $banRepo = $this->createMock(BanRepository::class);
        $ban = $this->createBan();

        $banRepo->expects(self::once())->method('save');

        $service = new BanService(
            $banRepo,
            $this->createStub(VoteRepository::class),
            $this->createStub(ChatConfigServiceInterface::class),
            $this->createStub(TelegramChatMemberApiInterface::class),
            $this->createStub(TelegramMessageApiInterface::class),
            $this->createStub(RequestHistoryRepository::class),
            new NullLogger(),
        );

        $service->forgiveBan($ban);

        self::assertSame(BanStatus::CANCELED, $ban->getStatus());
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

    private function createBan(
        int $chatId = -1001180970364,
        int $spammerId = 7816394199,
        int $reporterId = 217708876,
    ): TelegramChatUserBanEntity {
        return TelegramChatUserBanEntity::create(
            $chatId,
            $reporterId,
            $spammerId,
            12345
        );
    }
}
