<?php

declare(strict_types=1);

namespace App\Tests\Unit\Handler\Ban;

use App\Application\Command\Telegram\Ban\StartBanCommand;
use App\Application\Handler\Ban\StartBanHandler;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Service\BanMessageFormatterInterface;
use App\Domain\Telegram\Service\BanProcessServiceInterface;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\SpammerMessageServiceInterface;
use App\Domain\Telegram\Service\TelegramChatMemberApiInterface;
use App\Domain\Telegram\Service\TelegramMessageApiInterface;
use App\Domain\Telegram\Service\TrustServiceInterface;
use App\Domain\Telegram\Service\UserPersisterInterface;
use App\Domain\Telegram\ValueObject\Bot\TelegramChatMember;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;
use App\Tests\Factory\EntityFactory;
use App\Tests\Factory\TelegramUpdateFactory;
use App\Tests\TestCase\AbstractUnitTestCase;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;
use RuntimeException;

final class StartBanHandlerTest extends AbstractUnitTestCase
{
    private const int CHAT_ID = -1001180970364;
    private const int REPORTER_ID = 217708876;
    private const int SPAMMER_ID = 7816394199;
    private const int SPAM_MESSAGE_ID = 500;

    private Stub $banRepository;
    private Stub $banProcessService;
    private Stub $chatMemberApi;
    private Stub $messageApi;
    private Stub $spammerMessageService;
    private Stub $trustService;
    private Stub $chatConfigService;
    private Stub $messageFormatter;
    private Stub $userPersister;
    private StartBanHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->banRepository = $this->createStub(BanRepository::class);
        $this->banProcessService = $this->createStub(BanProcessServiceInterface::class);
        $this->chatMemberApi = $this->createStub(TelegramChatMemberApiInterface::class);
        $this->messageApi = $this->createStub(TelegramMessageApiInterface::class);
        $this->spammerMessageService = $this->createStub(SpammerMessageServiceInterface::class);
        $this->trustService = $this->createStub(TrustServiceInterface::class);
        $this->chatConfigService = $this->createStub(ChatConfigServiceInterface::class);
        $this->messageFormatter = $this->createStub(BanMessageFormatterInterface::class);
        $this->userPersister = $this->createStub(UserPersisterInterface::class);

        $this->handler = new StartBanHandler(
            $this->banRepository,
            $this->banProcessService,
            $this->chatMemberApi,
            $this->messageApi,
            $this->spammerMessageService,
            $this->trustService,
            $this->chatConfigService,
            $this->messageFormatter,
            $this->userPersister,
            new NullLogger(),
        );
    }

    public function testHandleHappyPathBanCreated(): void
    {
        $spammerMessage = $this->createSpammerMessage();
        $chatMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_MEMBER);
        $spammerUser = EntityFactory::createUser(self::CHAT_ID, self::SPAMMER_ID);
        $banMessage = $this->createBotMessage(999);

        $this->spammerMessageService->method('getSpammerMessage')->willReturn($spammerMessage);
        $this->chatMemberApi->method('getChatMember')->willReturn($chatMember);
        $this->trustService->method('isUserTrusted')->willReturn(false);
        $this->userPersister->method('persist')->willReturn($spammerUser);
        $this->banRepository->method('findBySpamMessage')->willReturn(null);
        $this->chatConfigService->method('getVotesRequired')->willReturn(3);
        $this->messageFormatter->method('formatStartBanMessage')->willReturn('ban text');
        $this->messageFormatter->method('formatInitialVoteMessage')->willReturn('vote text');
        $this->messageFormatter->method('formatVoteButtonText')->willReturn('btn');
        $this->messageApi->method('sendMessage')->willReturn($banMessage);

        $banProcessService = $this->createMock(BanProcessServiceInterface::class);
        $banProcessService
            ->expects(self::once())
            ->method('initiateBan');

        $handler = new StartBanHandler(
            $this->banRepository,
            $banProcessService,
            $this->chatMemberApi,
            $this->messageApi,
            $this->spammerMessageService,
            $this->trustService,
            $this->chatConfigService,
            $this->messageFormatter,
            $this->userPersister,
            new NullLogger(),
        );

        $command = $this->createCommand();
        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_STARTED, $result);
    }

    public function testHandleChatDisabledReturnsDisabledMessage(): void
    {
        $chat = EntityFactory::createChat(self::CHAT_ID, ['isEnabled' => false]);
        $user = EntityFactory::createUser(self::CHAT_ID, self::REPORTER_ID);
        $update = TelegramUpdateFactory::createBanCommand(self::CHAT_ID, self::REPORTER_ID, self::SPAM_MESSAGE_ID, self::SPAMMER_ID);
        $command = new StartBanCommand($update, $chat, $user);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_BOT_DISABLED, $result);
    }

    public function testHandleSpammerMessageNotFoundReturns404(): void
    {
        $command = $this->createCommand();

        $this->spammerMessageService->method('getSpammerMessage')->willReturn(null);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_SPAM_404, $result);
    }

    public function testHandleSpammerMessageServiceThrowsReturns404(): void
    {
        $command = $this->createCommand();

        $this->spammerMessageService->method('getSpammerMessage')
            ->willThrowException(new RuntimeException('API error'));

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_SPAM_404, $result);
    }

    public function testHandleSpammerIsAdminReturnsImmune(): void
    {
        $command = $this->createCommand();
        $spammerMessage = $this->createSpammerMessage();
        $adminMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_ADMIN);

        $this->spammerMessageService->method('getSpammerMessage')->willReturn($spammerMessage);
        $this->chatMemberApi->method('getChatMember')->willReturn($adminMember);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_ADMIN_IS_IMMUNE, $result);
    }

    public function testHandleSpammerNotFoundInChatReturnsImmune(): void
    {
        $command = $this->createCommand();
        $spammerMessage = $this->createSpammerMessage();

        $this->spammerMessageService->method('getSpammerMessage')->willReturn($spammerMessage);
        $this->chatMemberApi->method('getChatMember')->willReturn(null);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_ADMIN_IS_IMMUNE, $result);
    }

    public function testHandleSelfBanReturnsNotSupported(): void
    {
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::REPORTER_ID);
        $update = TelegramUpdateFactory::createBanCommand(self::CHAT_ID, self::REPORTER_ID, self::SPAM_MESSAGE_ID, self::REPORTER_ID);
        $command = new StartBanCommand($update, $chat, $user);

        $spammerMessage = $this->createSpammerMessage(self::REPORTER_ID);
        $chatMember = $this->createChatMember(self::REPORTER_ID, TelegramChatMember::CHAT_MEMBER_MEMBER);

        $this->spammerMessageService->method('getSpammerMessage')->willReturn($spammerMessage);
        $this->chatMemberApi->method('getChatMember')->willReturn($chatMember);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED, $result);
    }

    public function testHandleTrustedUserReturnsUserIsTrusted(): void
    {
        $command = $this->createCommand();
        $spammerMessage = $this->createSpammerMessage();
        $chatMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_MEMBER);

        $this->spammerMessageService->method('getSpammerMessage')->willReturn($spammerMessage);
        $this->chatMemberApi->method('getChatMember')->willReturn($chatMember);
        $this->trustService->method('isUserTrusted')->willReturn(true);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_USER_IS_TRUSTED, $result);
    }

    public function testHandleBanAlreadyExistsReturnsAlreadyStarted(): void
    {
        $command = $this->createCommand();
        $spammerMessage = $this->createSpammerMessage();
        $chatMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_MEMBER);
        $existingBan = EntityFactory::createBan(self::CHAT_ID, self::SPAMMER_ID, self::REPORTER_ID);
        $spammerUser = EntityFactory::createUser(self::CHAT_ID, self::SPAMMER_ID);

        $this->spammerMessageService->method('getSpammerMessage')->willReturn($spammerMessage);
        $this->chatMemberApi->method('getChatMember')->willReturn($chatMember);
        $this->trustService->method('isUserTrusted')->willReturn(false);
        $this->userPersister->method('persist')->willReturn($spammerUser);
        $this->banRepository->method('findBySpamMessage')->willReturn($existingBan);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_ALREADY_STARTED, $result);
    }

    public function testHandleSendMessageFailsReturnsApiError(): void
    {
        $command = $this->createCommand();
        $spammerMessage = $this->createSpammerMessage();
        $chatMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_MEMBER);
        $spammerUser = EntityFactory::createUser(self::CHAT_ID, self::SPAMMER_ID);

        $this->spammerMessageService->method('getSpammerMessage')->willReturn($spammerMessage);
        $this->chatMemberApi->method('getChatMember')->willReturn($chatMember);
        $this->trustService->method('isUserTrusted')->willReturn(false);
        $this->userPersister->method('persist')->willReturn($spammerUser);
        $this->banRepository->method('findBySpamMessage')->willReturn(null);
        $this->chatConfigService->method('getVotesRequired')->willReturn(3);
        $this->messageFormatter->method('formatStartBanMessage')->willReturn('ban text');
        $this->messageFormatter->method('formatInitialVoteMessage')->willReturn('vote text');
        $this->messageFormatter->method('formatVoteButtonText')->willReturn('btn');
        $this->messageApi->method('sendMessage')->willReturn(null);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_API_ERROR, $result);
    }

    public function testHandleSpammerIsOwnerReturnsImmune(): void
    {
        $command = $this->createCommand();
        $spammerMessage = $this->createSpammerMessage();
        $ownerMember = $this->createChatMember(self::SPAMMER_ID, TelegramChatMember::CHAT_MEMBER_OWNER);

        $this->spammerMessageService->method('getSpammerMessage')->willReturn($spammerMessage);
        $this->chatMemberApi->method('getChatMember')->willReturn($ownerMember);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_ADMIN_IS_IMMUNE, $result);
    }

    private function createCommand(): StartBanCommand
    {
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::REPORTER_ID);
        $update = TelegramUpdateFactory::createBanCommand(
            self::CHAT_ID,
            self::REPORTER_ID,
            self::SPAM_MESSAGE_ID,
            self::SPAMMER_ID,
        );

        return new StartBanCommand($update, $chat, $user);
    }

    private function createSpammerMessage(int $fromId = self::SPAMMER_ID): TelegramMessage
    {
        $message = new TelegramMessage();
        $message->message_id = self::SPAM_MESSAGE_ID;
        $message->date = time();
        $message->chat = new TelegramMessageChat();
        $message->chat->id = self::CHAT_ID;
        $message->from = new TelegramMessageFrom();
        $message->from->id = $fromId;
        $message->from->username = 'spammer';
        $message->from->first_name = 'Spammer';
        $message->from->is_bot = false;
        $message->from->language_code = 'en';
        $message->sticker = null;
        $message->document = null;

        return $message;
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

    private function createBotMessage(int $messageId): TelegramMessage
    {
        $message = new TelegramMessage();
        $message->message_id = $messageId;
        $message->date = time();
        $message->chat = new TelegramMessageChat();
        $message->chat->id = self::CHAT_ID;
        $message->from = new TelegramMessageFrom();
        $message->sticker = null;
        $message->document = null;

        return $message;
    }
}
