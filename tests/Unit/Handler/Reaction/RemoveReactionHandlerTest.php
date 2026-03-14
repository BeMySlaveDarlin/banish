<?php

declare(strict_types=1);

namespace App\Tests\Unit\Handler\Reaction;

use App\Application\Command\Telegram\Reaction\ReactionRemovedCommand;
use App\Application\Handler\Reaction\RemoveReactionHandler;
use App\Domain\Telegram\Constants\Messages;
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

final class RemoveReactionHandlerTest extends AbstractUnitTestCase
{
    private const int CHAT_ID = -1001180970364;
    private const int VOTER_ID = 217708876;
    private const int SPAM_MESSAGE_ID = 500;

    private Stub $banRepository;
    private Stub $voteRepository;
    private Stub $banProcessService;
    private Stub $chatConfigService;
    private RemoveReactionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->banRepository = $this->createStub(BanRepository::class);
        $this->voteRepository = $this->createStub(VoteRepository::class);
        $this->banProcessService = $this->createStub(BanProcessServiceInterface::class);
        $this->chatConfigService = $this->createStub(ChatConfigServiceInterface::class);

        $this->handler = new RemoveReactionHandler(
            $this->banRepository,
            $this->voteRepository,
            $this->banProcessService,
            $this->chatConfigService,
        );
    }

    public function testHandleHappyPathVoteDeletedAndVerdictChecked(): void
    {
        $ban = EntityFactory::createBan(self::CHAT_ID, 999, self::VOTER_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::VOTER_ID);
        $vote = EntityFactory::createVote($ban, $user, VoteType::BAN);
        $voteResult = new VoteResult([], [], 0, 0, 3, false, false);

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->banRepository->method('findBySpamMessage')->willReturn($ban);

        $voteRepository = $this->createMock(VoteRepository::class);
        $voteRepository->method('findByUserAndBan')->willReturn($vote);
        $voteRepository->expects(self::once())->method('delete')->with($vote);

        $banProcessService = $this->createMock(BanProcessServiceInterface::class);
        $banProcessService->expects(self::once())
            ->method('checkAndExecuteVerdict')
            ->willReturn($voteResult);

        $handler = new RemoveReactionHandler(
            $this->banRepository,
            $voteRepository,
            $banProcessService,
            $this->chatConfigService,
        );

        $command = $this->createCommand();
        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_STARTED, $result);
    }

    public function testHandleNoExistingVoteDoesNotDelete(): void
    {
        $ban = EntityFactory::createBan(self::CHAT_ID, 999, self::VOTER_ID);

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->banRepository->method('findBySpamMessage')->willReturn($ban);

        $voteRepository = $this->createMock(VoteRepository::class);
        $voteRepository->method('findByUserAndBan')->willReturn(null);
        $voteRepository->expects(self::never())->method('delete');

        $handler = new RemoveReactionHandler(
            $this->banRepository,
            $voteRepository,
            $this->banProcessService,
            $this->chatConfigService,
        );

        $command = $this->createCommand();
        $result = $handler->handle($command);

        self::assertSame(Messages::MESSAGE_BAN_STARTED, $result);
    }

    public function testHandleBanNotFoundReturnsNotSupported(): void
    {
        $command = $this->createCommand();

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(true);
        $this->banRepository->method('findBySpamMessage')->willReturn(null);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED, $result);
    }

    public function testHandleChatDisabledReturnsDisabledMessage(): void
    {
        $chat = EntityFactory::createChat(self::CHAT_ID, ['isEnabled' => false]);
        $user = EntityFactory::createUser(self::CHAT_ID, self::VOTER_ID);
        $update = TelegramUpdateFactory::createReaction(self::CHAT_ID, self::VOTER_ID, self::SPAM_MESSAGE_ID, 'emoji');
        $reaction = $update->message_reaction;
        self::assertNotNull($reaction);
        $reaction->new_reaction = [];
        $command = new ReactionRemovedCommand($update, $chat, $user);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_BOT_DISABLED, $result);
    }

    public function testHandleReactionsDisabledReturnsNotSupported(): void
    {
        $command = $this->createCommand();

        $this->chatConfigService->method('isReactionsEnabled')->willReturn(false);

        $result = $this->handler->handle($command);

        self::assertSame(Messages::MESSAGE_NOT_SUPPORTED, $result);
    }

    private function createCommand(): ReactionRemovedCommand
    {
        $chat = EntityFactory::createChat(self::CHAT_ID);
        $user = EntityFactory::createUser(self::CHAT_ID, self::VOTER_ID);
        $update = TelegramUpdateFactory::createReaction(
            self::CHAT_ID,
            self::VOTER_ID,
            self::SPAM_MESSAGE_ID,
            'emoji',
        );
        $reaction = $update->message_reaction;
        self::assertNotNull($reaction);
        $reaction->new_reaction = [];

        return new ReactionRemovedCommand($update, $chat, $user);
    }
}
