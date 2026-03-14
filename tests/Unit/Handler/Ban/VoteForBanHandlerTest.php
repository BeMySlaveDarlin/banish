<?php

declare(strict_types=1);

namespace App\Tests\Unit\Handler\Ban;

use App\Application\Command\Telegram\Ban\VoteForBanCommand;
use App\Application\Handler\Ban\VoteForBanHandler;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\BanMessageFormatterInterface;
use App\Domain\Telegram\Service\BanProcessServiceInterface;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\TelegramMessageApiInterface;
use App\Domain\Telegram\ValueObject\VoteResult;
use App\Tests\Factory\EntityFactory;
use App\Tests\Factory\TelegramUpdateFactory;
use App\Tests\TestCase\AbstractUnitTestCase;
use PHPUnit\Framework\MockObject\Stub;

final class VoteForBanHandlerTest extends AbstractUnitTestCase
{
    private const int CHAT_ID = -1001180970364;
    private const int VOTER_ID = 217708876;
    private const int BAN_MESSAGE_ID = 12345;

    private Stub $banRepository;
    private Stub $chatConfigService;
    private Stub $userRepository;
    private Stub $banProcessService;
    private Stub $messageFormatter;
    private Stub $messageApi;
    private VoteForBanHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->banRepository = $this->createStub(BanRepository::class);
        $this->chatConfigService = $this->createStub(ChatConfigServiceInterface::class);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->banProcessService = $this->createStub(BanProcessServiceInterface::class);
        $this->messageFormatter = $this->createStub(BanMessageFormatterInterface::class);
        $this->messageApi = $this->createStub(TelegramMessageApiInterface::class);

        $this->handler = new VoteForBanHandler(
            $this->banRepository,
            $this->chatConfigService,
            $this->userRepository,
            $this->banProcessService,
            $this->messageFormatter,
            $this->messageApi,
        );
    }

    public function testHandleVoteBanHappyPath(): void
    {
        $ban = EntityFactory::createBan(self::CHAT_ID, 999, self::VOTER_ID);
        $voteResult = $this->createVoteResult();

        $this->banRepository->method('findActiveBan')->willReturn($ban);
        $this->userRepository->method('findByChatAndUser')->willReturn(null);
        $this->chatConfigService->method('isDeleteOnlyEnabled')->willReturn(false);
        $this->messageFormatter->method('formatVoteMessage')->willReturn('vote msg');
        $this->messageFormatter->method('formatVoteButtonText')->willReturn('btn');

        $banProcessService = $this->createMock(BanProcessServiceInterface::class);
        $banProcessService->expects(self::once())
            ->method('processVote')
            ->willReturn($voteResult);

        $messageApi = $this->createMock(TelegramMessageApiInterface::class);
        $messageApi->expects(self::once())->method('editMessageText');

        $handler = new VoteForBanHandler(
            $this->banRepository,
            $this->chatConfigService,
            $this->userRepository,
            $banProcessService,
            $this->messageFormatter,
            $messageApi,
        );

        $command = $this->createCommand(VoteType::BAN->value);
        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_PROCESSED, $result);
    }

    public function testHandleVoteForgiveHappyPath(): void
    {
        $ban = EntityFactory::createBan(self::CHAT_ID, 999, self::VOTER_ID);
        $voteResult = $this->createVoteResult();

        $this->banRepository->method('findActiveBan')->willReturn($ban);
        $this->userRepository->method('findByChatAndUser')->willReturn(null);
        $this->chatConfigService->method('isDeleteOnlyEnabled')->willReturn(false);
        $this->messageFormatter->method('formatVoteMessage')->willReturn('vote msg');
        $this->messageFormatter->method('formatVoteButtonText')->willReturn('btn');

        $banProcessService = $this->createMock(BanProcessServiceInterface::class);
        $banProcessService->expects(self::once())
            ->method('processVote')
            ->willReturn($voteResult);

        $messageApi = $this->createMock(TelegramMessageApiInterface::class);
        $messageApi->expects(self::once())->method('editMessageText');

        $handler = new VoteForBanHandler(
            $this->banRepository,
            $this->chatConfigService,
            $this->userRepository,
            $banProcessService,
            $this->messageFormatter,
            $messageApi,
        );

        $command = $this->createCommand(VoteType::FORGIVE->value);
        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_PROCESSED, $result);
    }

    public function testHandleChatDisabledReturnsDisabledMessage(): void
    {
        $chat = EntityFactory::createChat(self::CHAT_ID, ['isEnabled' => false]);
        $user = EntityFactory::createUser(self::CHAT_ID, self::VOTER_ID);
        $update = TelegramUpdateFactory::createCallbackQuery(self::CHAT_ID, self::VOTER_ID, VoteType::BAN->value, self::BAN_MESSAGE_ID);
        $command = new VoteForBanCommand($update, $chat, $user);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_BOT_DISABLED, $result);
    }

    public function testHandleUnsupportedVoteTypeReturnsNotSupportedCb(): void
    {
        $command = $this->createCommand('invalid_vote');

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED_CB, $result);
    }

    public function testHandleBanNotFoundReturnsBan404(): void
    {
        $command = $this->createCommand(VoteType::BAN->value);

        $this->banRepository->method('findActiveBan')->willReturn(null);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_404, $result);
    }

    public function testHandleEmptyCallbackDataReturnsNotSupportedCb(): void
    {
        $command = $this->createCommand('');

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED_CB, $result);
    }

    public function testHandleProcessVoteUpdatesMessage(): void
    {
        $ban = EntityFactory::createBan(self::CHAT_ID, 999, self::VOTER_ID);
        $voteResult = $this->createVoteResult();

        $this->banRepository->method('findActiveBan')->willReturn($ban);
        $this->banProcessService->method('processVote')->willReturn($voteResult);
        $this->userRepository->method('findByChatAndUser')->willReturn(null);
        $this->chatConfigService->method('isDeleteOnlyEnabled')->willReturn(false);
        $this->messageFormatter->method('formatVoteMessage')->willReturn('vote msg');
        $this->messageFormatter->method('formatVoteButtonText')->willReturn('btn');

        $messageApi = $this->createMock(TelegramMessageApiInterface::class);
        $messageApi->expects(self::once())->method('editMessageText');

        $handler = new VoteForBanHandler(
            $this->banRepository,
            $this->chatConfigService,
            $this->userRepository,
            $this->banProcessService,
            $this->messageFormatter,
            $messageApi,
        );

        $command = $this->createCommand(VoteType::BAN->value);
        $handler->handle($command);
    }

    private function createCommand(string $callbackData): VoteForBanCommand
    {
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::VOTER_ID);
        $update = TelegramUpdateFactory::createCallbackQuery(
            self::CHAT_ID,
            self::VOTER_ID,
            $callbackData,
            self::BAN_MESSAGE_ID,
        );

        return new VoteForBanCommand($update, $chat, $user);
    }

    private function createVoteResult(): VoteResult
    {
        return new VoteResult(
            upVotes: [],
            downVotes: [],
            upCount: 1,
            downCount: 0,
            requiredVotes: 3,
            shouldBan: false,
            shouldForgive: false,
        );
    }
}
