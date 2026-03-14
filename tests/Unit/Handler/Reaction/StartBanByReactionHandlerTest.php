<?php

declare(strict_types=1);

namespace App\Tests\Unit\Handler\Reaction;

use App\Application\Command\Telegram\Reaction\StartBanByReactionCommand;
use App\Application\Handler\Reaction\StartBanByReactionHandler;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramRequestHistoryEntity;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Service\BanProcessServiceInterface;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\TelegramChatMemberApiInterface;
use App\Domain\Telegram\Service\TrustServiceInterface;
use App\Domain\Telegram\Service\UserPersisterInterface;
use App\Domain\Telegram\ValueObject\Bot\TelegramChatMember;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;
use App\Domain\Telegram\ValueObject\VoteResult;
use App\Tests\Factory\EntityFactory;
use App\Tests\Factory\TelegramUpdateFactory;
use App\Tests\TestCase\AbstractUnitTestCase;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;

final class StartBanByReactionHandlerTest extends AbstractUnitTestCase
{
    private const int CHAT_ID = -1001180970364;
    private const int REPORTER_ID = 217708876;
    private const int SPAMMER_ID = 7816394199;
    private const int SPAM_MESSAGE_ID = 500;
    private const string BAN_EMOJI = "\xF0\x9F\x91\x8E";

    private Stub $banRepository;
    private Stub $requestHistoryRepository;
    private Stub $banProcessService;
    private Stub $chatMemberApi;
    private Stub $trustService;
    private Stub $chatConfigService;
    private Stub $userPersister;
    private StartBanByReactionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->banRepository = $this->createStub(BanRepository::class);
        $this->requestHistoryRepository = $this->createStub(RequestHistoryRepository::class);
        $this->banProcessService = $this->createStub(BanProcessServiceInterface::class);
        $this->chatMemberApi = $this->createStub(TelegramChatMemberApiInterface::class);
        $this->trustService = $this->createStub(TrustServiceInterface::class);
        $this->chatConfigService = $this->createStub(ChatConfigServiceInterface::class);
        $this->userPersister = $this->createStub(UserPersisterInterface::class);

        $this->handler = new StartBanByReactionHandler(
            $this->banRepository,
            $this->requestHistoryRepository,
            $this->banProcessService,
            $this->chatMemberApi,
            $this->trustService,
            $this->chatConfigService,
            $this->userPersister,
            new NullLogger(),
        );
    }

    public function testHandleHappyPathBanCreatedByReaction(): void
    {
        $command = $this->createCommand(self::BAN_EMOJI);
        $history = $this->createHistory();
        $chatMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_MEMBER);
        $spammerUser = EntityFactory::createUser(self::CHAT_ID, self::SPAMMER_ID);
        $ban = EntityFactory::createBan(self::CHAT_ID, self::SPAMMER_ID, self::REPORTER_ID);
        $voteResult = $this->createVoteResult();

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->chatConfigService->method('getBanEmoji')->willReturn(self::BAN_EMOJI);
        $this->banRepository->method('findBySpamMessage')->willReturn(null);
        $this->requestHistoryRepository->method('findMessageByReaction')->willReturn($history);
        $this->chatMemberApi->method('getChatMember')->willReturn($chatMember);
        $this->trustService->method('isUserTrusted')
            ->willReturnMap([
                [$command->chat, self::SPAMMER_ID, false],
                [$command->chat, self::REPORTER_ID, true],
            ]);
        $this->userPersister->method('persist')->willReturn($spammerUser);
        $this->banProcessService->method('initiateBan')->willReturn($ban);
        $this->banProcessService->method('checkAndExecuteVerdict')->willReturn($voteResult);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_STARTED, $result);
    }

    public function testHandleChatDisabledReturnsDisabledMessage(): void
    {
        $chat = EntityFactory::createChat(self::CHAT_ID, ['isEnabled' => false]);
        $user = EntityFactory::createUser(self::CHAT_ID, self::REPORTER_ID);
        $update = TelegramUpdateFactory::createReaction(self::CHAT_ID, self::REPORTER_ID, self::SPAM_MESSAGE_ID, self::BAN_EMOJI);
        $command = new StartBanByReactionCommand($update, $chat, $user);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_BOT_DISABLED, $result);
    }

    public function testHandleReactionsDisabledReturnsNotSupported(): void
    {
        $command = $this->createCommand(self::BAN_EMOJI);

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(false);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED, $result);
    }

    public function testHandleWrongEmojiReturnsNotSupported(): void
    {
        $command = $this->createCommand(self::BAN_EMOJI);

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->chatConfigService->method('getBanEmoji')->willReturn('different_emoji');
        $this->banRepository->method('findBySpamMessage')->willReturn(null);
        $this->requestHistoryRepository->method('findMessageByReaction')->willReturn($this->createHistory());
        $chatMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_MEMBER);
        $this->chatMemberApi->method('getChatMember')->willReturn($chatMember);
        $this->trustService->method('isUserTrusted')->willReturn(false);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED, $result);
    }

    public function testHandleSpammerIsAdminReturnsImmune(): void
    {
        $command = $this->createCommand(self::BAN_EMOJI);
        $history = $this->createHistory();
        $adminMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_ADMIN);

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->chatConfigService->method('getBanEmoji')->willReturn(self::BAN_EMOJI);
        $this->banRepository->method('findBySpamMessage')->willReturn(null);
        $this->requestHistoryRepository->method('findMessageByReaction')->willReturn($history);
        $this->chatMemberApi->method('getChatMember')->willReturn($adminMember);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_ADMIN_IS_IMMUNE, $result);
    }

    public function testHandleUntrustedReporterReturnsNotSupported(): void
    {
        $command = $this->createCommand(self::BAN_EMOJI);
        $history = $this->createHistory();
        $chatMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_MEMBER);

        $user = EntityFactory::createUser(self::CHAT_ID, self::REPORTER_ID, isAdmin: false);

        $chat = EntityFactory::createChat(self::CHAT_ID);
        $update = TelegramUpdateFactory::createReaction(self::CHAT_ID, self::REPORTER_ID, self::SPAM_MESSAGE_ID, self::BAN_EMOJI);
        $command = new StartBanByReactionCommand($update, $chat, $user);

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->chatConfigService->method('getBanEmoji')->willReturn(self::BAN_EMOJI);
        $this->banRepository->method('findBySpamMessage')->willReturn(null);
        $this->requestHistoryRepository->method('findMessageByReaction')->willReturn($history);
        $this->chatMemberApi->method('getChatMember')->willReturn($chatMember);
        $this->trustService->method('isUserTrusted')->willReturn(false);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED, $result);
    }

    public function testHandleBanAlreadyExistsVotesInstead(): void
    {
        $existingBan = EntityFactory::createBan(self::CHAT_ID, self::SPAMMER_ID, self::REPORTER_ID);
        $voteResult = $this->createVoteResult();

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->chatConfigService->method('getBanEmoji')->willReturn(self::BAN_EMOJI);
        $this->chatConfigService->method('getForgiveEmoji')->willReturn('forgive_emoji');
        $this->banRepository->method('findBySpamMessage')->willReturn($existingBan);

        $banProcessService = $this->createMock(BanProcessServiceInterface::class);
        $banProcessService->expects(self::once())
            ->method('processVote')
            ->willReturn($voteResult);

        $handler = new StartBanByReactionHandler(
            $this->banRepository,
            $this->requestHistoryRepository,
            $banProcessService,
            $this->chatMemberApi,
            $this->trustService,
            $this->chatConfigService,
            $this->userPersister,
            new NullLogger(),
        );

        $command = $this->createCommand(self::BAN_EMOJI);
        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_PROCESSED, $result);
    }

    public function testHandleTrustedSpammerReturnsUserIsTrusted(): void
    {
        $command = $this->createCommand(self::BAN_EMOJI);
        $history = $this->createHistory();
        $chatMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_MEMBER);

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->chatConfigService->method('getBanEmoji')->willReturn(self::BAN_EMOJI);
        $this->banRepository->method('findBySpamMessage')->willReturn(null);
        $this->requestHistoryRepository->method('findMessageByReaction')->willReturn($history);
        $this->chatMemberApi->method('getChatMember')->willReturn($chatMember);
        $this->trustService->method('isUserTrusted')
            ->with($command->chat, self::SPAMMER_ID)
            ->willReturn(true);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_USER_IS_TRUSTED, $result);
    }

    private function createCommand(string $emoji): StartBanByReactionCommand
    {
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::REPORTER_ID, isAdmin: true);
        $update = TelegramUpdateFactory::createReaction(
            self::CHAT_ID,
            self::REPORTER_ID,
            self::SPAM_MESSAGE_ID,
            $emoji,
        );

        return new StartBanByReactionCommand($update, $chat, $user);
    }

    private function createHistory(): TelegramRequestHistoryEntity
    {
        $history = new TelegramRequestHistoryEntity();
        $history->chatId = self::CHAT_ID;
        $history->fromId = self::SPAMMER_ID;
        $history->messageId = self::SPAM_MESSAGE_ID;
        $history->updateId = 1;

        return $history;
    }

    private function createChatMember(int $userId, string $status): TelegramChatMember
    {
        $member = new TelegramChatMember();
        $member->status = $status;
        $member->user = new TelegramMessageFrom();
        $member->user->id = $userId;
        $member->user->username = 'user_' . $userId;
        $member->user->is_bot = false;
        $member->user->language_code = 'en';

        return $member;
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
