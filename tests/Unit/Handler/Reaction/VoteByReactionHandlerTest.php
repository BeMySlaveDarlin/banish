<?php

declare(strict_types=1);

namespace App\Tests\Unit\Handler\Reaction;

use App\Application\Command\Telegram\Reaction\VoteByReactionCommand;
use App\Application\Handler\Reaction\VoteByReactionHandler;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\Service\BanProcessServiceInterface;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\ValueObject\VoteResult;
use App\Tests\Factory\EntityFactory;
use App\Tests\Factory\TelegramUpdateFactory;
use App\Tests\TestCase\AbstractUnitTestCase;
use PHPUnit\Framework\MockObject\Stub;

final class VoteByReactionHandlerTest extends AbstractUnitTestCase
{
    private const int CHAT_ID = -1001180970364;
    private const int VOTER_ID = 217708876;
    private const int SPAM_MESSAGE_ID = 500;
    private const string BAN_EMOJI = "\xF0\x9F\x91\x8E";
    private const string FORGIVE_EMOJI = "\xF0\x9F\x91\x8D";

    private Stub $banRepository;
    private Stub $voteRepository;
    private Stub $banProcessService;
    private Stub $chatConfigService;
    private VoteByReactionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->banRepository = $this->createStub(BanRepository::class);
        $this->voteRepository = $this->createStub(VoteRepository::class);
        $this->banProcessService = $this->createStub(BanProcessServiceInterface::class);
        $this->chatConfigService = $this->createStub(ChatConfigServiceInterface::class);

        $this->handler = new VoteByReactionHandler(
            $this->banRepository,
            $this->voteRepository,
            $this->banProcessService,
            $this->chatConfigService,
        );
    }

    public function testHandleVoteByReactionHappyPath(): void
    {
        $ban = EntityFactory::createBan(self::CHAT_ID, 999, self::VOTER_ID);
        $voteResult = $this->createVoteResult();

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->chatConfigService->method('getBanEmoji')->willReturn(self::BAN_EMOJI);
        $this->chatConfigService->method('getForgiveEmoji')->willReturn(self::FORGIVE_EMOJI);
        $this->banRepository->method('findBySpamMessage')->willReturn($ban);

        $banProcessService = $this->createMock(BanProcessServiceInterface::class);
        $banProcessService->expects(self::once())
            ->method('processVote')
            ->willReturn($voteResult);

        $handler = new VoteByReactionHandler(
            $this->banRepository,
            $this->voteRepository,
            $banProcessService,
            $this->chatConfigService,
        );

        $command = $this->createCommand(self::BAN_EMOJI);
        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_PROCESSED, $result);
    }

    public function testHandleChatDisabledReturnsDisabledMessage(): void
    {
        $chat = EntityFactory::createChat(self::CHAT_ID, ['isEnabled' => false]);
        $user = EntityFactory::createUser(self::CHAT_ID, self::VOTER_ID);
        $update = TelegramUpdateFactory::createReaction(self::CHAT_ID, self::VOTER_ID, self::SPAM_MESSAGE_ID, self::BAN_EMOJI);
        $command = new VoteByReactionCommand($update, $chat, $user);

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

    public function testHandleBanNotFoundReturnsBan404(): void
    {
        $command = $this->createCommand(self::BAN_EMOJI);

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->banRepository->method('findBySpamMessage')->willReturn(null);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_404, $result);
    }

    public function testHandleWrongEmojiReturnsNotSupported(): void
    {
        $command = $this->createCommand(self::BAN_EMOJI);
        $ban = EntityFactory::createBan(self::CHAT_ID, 999, self::VOTER_ID);

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->chatConfigService->method('getBanEmoji')->willReturn('other_emoji');
        $this->chatConfigService->method('getForgiveEmoji')->willReturn('another_emoji');
        $this->banRepository->method('findBySpamMessage')->willReturn($ban);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED, $result);
    }

    public function testHandleReactionRemovedDeletesVoteAndChecksVerdict(): void
    {
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::VOTER_ID);
        $update = TelegramUpdateFactory::createReaction(self::CHAT_ID, self::VOTER_ID, self::SPAM_MESSAGE_ID, self::BAN_EMOJI);
        $reaction = $update->message_reaction;
        self::assertNotNull($reaction);
        $reaction->new_reaction = [];
        $command = new VoteByReactionCommand($update, $chat, $user);

        $ban = EntityFactory::createBan(self::CHAT_ID, 999, self::VOTER_ID);
        $vote = EntityFactory::createVote($ban, $user, VoteType::BAN);
        $voteResult = $this->createVoteResult();

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->banRepository->method('findBySpamMessage')->willReturn($ban);

        $voteRepository = $this->createMock(VoteRepository::class);
        $voteRepository->method('findByUserAndBan')->willReturn($vote);
        $voteRepository->expects(self::once())->method('delete')->with($vote);

        $banProcessService = $this->createMock(BanProcessServiceInterface::class);
        $banProcessService->expects(self::once())
            ->method('checkAndExecuteVerdict')
            ->willReturn($voteResult);

        $handler = new VoteByReactionHandler(
            $this->banRepository,
            $voteRepository,
            $banProcessService,
            $this->chatConfigService,
        );

        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_PROCESSED, $result);
    }

    private function createCommand(string $emoji): VoteByReactionCommand
    {
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::VOTER_ID);
        $update = TelegramUpdateFactory::createReaction(
            self::CHAT_ID,
            self::VOTER_ID,
            self::SPAM_MESSAGE_ID,
            $emoji,
        );

        return new VoteByReactionCommand($update, $chat, $user);
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
