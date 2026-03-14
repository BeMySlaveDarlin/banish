<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\VoteService;
use App\Domain\Common\ValueObject\JsonBValue;
use App\Tests\TestCase\AbstractUnitTestCase;

final class VoteServiceTest extends AbstractUnitTestCase
{
    public function testVoteCreatesNewVote(): void
    {
        $voteRepo = $this->createMock(VoteRepository::class);
        $configStub = $this->createStub(ChatConfigServiceInterface::class);

        $chat = $this->createChat();
        $user = $this->createUser();
        $ban = $this->createBan();
        $expectedVote = new TelegramChatUserBanVoteEntity();
        $expectedVote->vote = VoteType::BAN;

        $voteRepo->expects(self::once())->method('findByUserAndBan')->with($user, $ban)->willReturn(null);
        $voteRepo->expects(self::once())->method('createVote')
            ->with($user, $ban, $chat->chatId, VoteType::BAN)
            ->willReturn($expectedVote);
        $voteRepo->expects(self::once())->method('save')->with($expectedVote);

        $service = new VoteService($voteRepo, $configStub);
        $result = $service->vote($chat, $user, $ban, VoteType::BAN);

        self::assertSame(VoteType::BAN, $result->vote);
    }

    public function testVoteUpdatesExistingVote(): void
    {
        $voteRepo = $this->createMock(VoteRepository::class);
        $configStub = $this->createStub(ChatConfigServiceInterface::class);

        $chat = $this->createChat();
        $user = $this->createUser();
        $ban = $this->createBan();
        $existingVote = new TelegramChatUserBanVoteEntity();
        $existingVote->vote = VoteType::BAN;

        $voteRepo->expects(self::once())->method('findByUserAndBan')->with($user, $ban)->willReturn($existingVote);
        $voteRepo->expects(self::never())->method('createVote');
        $voteRepo->expects(self::once())->method('save')->with($existingVote);

        $service = new VoteService($voteRepo, $configStub);
        $result = $service->vote($chat, $user, $ban, VoteType::FORGIVE);

        self::assertSame(VoteType::FORGIVE, $result->vote);
    }

    public function testGetVoteResultShouldBanWhenEnoughVotes(): void
    {
        $chat = $this->createChat();
        $ban = $this->createBan();

        $banVote1 = $this->createVoteEntity($this->createUser(1));
        $banVote2 = $this->createVoteEntity($this->createUser(2));
        $banVote3 = $this->createVoteEntity($this->createUser(3));

        $voteStub = $this->createStub(VoteRepository::class);
        $voteStub->method('getVotersByType')
            ->willReturnCallback(static function ($ban, $type) use ($banVote1, $banVote2, $banVote3) {
                return $type === VoteType::BAN ? [$banVote1, $banVote2, $banVote3] : [];
            });

        $configStub = $this->createStub(ChatConfigServiceInterface::class);
        $configStub->method('getVotesRequired')->willReturn(3);

        $service = new VoteService($voteStub, $configStub);
        $result = $service->getVoteResult($chat, $ban);

        self::assertTrue($result->shouldBan);
        self::assertFalse($result->shouldForgive);
        self::assertSame(3, $result->upCount);
        self::assertSame(0, $result->downCount);
        self::assertSame(3, $result->requiredVotes);
    }

    public function testGetVoteResultShouldForgiveWhenEnoughVotes(): void
    {
        $chat = $this->createChat();
        $ban = $this->createBan();

        $forgiveVote1 = $this->createVoteEntity($this->createUser(1));
        $forgiveVote2 = $this->createVoteEntity($this->createUser(2));
        $forgiveVote3 = $this->createVoteEntity($this->createUser(3));

        $voteStub = $this->createStub(VoteRepository::class);
        $voteStub->method('getVotersByType')
            ->willReturnCallback(static function ($ban, $type) use ($forgiveVote1, $forgiveVote2, $forgiveVote3) {
                return $type === VoteType::FORGIVE ? [$forgiveVote1, $forgiveVote2, $forgiveVote3] : [];
            });

        $configStub = $this->createStub(ChatConfigServiceInterface::class);
        $configStub->method('getVotesRequired')->willReturn(3);

        $service = new VoteService($voteStub, $configStub);
        $result = $service->getVoteResult($chat, $ban);

        self::assertFalse($result->shouldBan);
        self::assertTrue($result->shouldForgive);
        self::assertSame(0, $result->upCount);
        self::assertSame(3, $result->downCount);
    }

    public function testGetVoteResultNotEnoughVotes(): void
    {
        $chat = $this->createChat();
        $ban = $this->createBan();

        $banVote1 = $this->createVoteEntity($this->createUser(1));

        $voteStub = $this->createStub(VoteRepository::class);
        $voteStub->method('getVotersByType')
            ->willReturnCallback(static function ($ban, $type) use ($banVote1) {
                return $type === VoteType::BAN ? [$banVote1] : [];
            });

        $configStub = $this->createStub(ChatConfigServiceInterface::class);
        $configStub->method('getVotesRequired')->willReturn(3);

        $service = new VoteService($voteStub, $configStub);
        $result = $service->getVoteResult($chat, $ban);

        self::assertFalse($result->shouldBan);
        self::assertFalse($result->shouldForgive);
        self::assertSame(1, $result->upCount);
        self::assertSame(0, $result->downCount);
    }

    public function testGetVoteResultFiltersNullUsers(): void
    {
        $chat = $this->createChat();
        $ban = $this->createBan();

        $banVote1 = $this->createVoteEntity($this->createUser(1));
        $banVote2 = $this->createVoteEntity(null);

        $voteStub = $this->createStub(VoteRepository::class);
        $voteStub->method('getVotersByType')
            ->willReturnCallback(static function ($ban, $type) use ($banVote1, $banVote2) {
                return $type === VoteType::BAN ? [$banVote1, $banVote2] : [];
            });

        $configStub = $this->createStub(ChatConfigServiceInterface::class);
        $configStub->method('getVotesRequired')->willReturn(3);

        $service = new VoteService($voteStub, $configStub);
        $result = $service->getVoteResult($chat, $ban);

        self::assertSame(1, $result->upCount);
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

    private function createUser(int $userId = 217708876): TelegramChatUserEntity
    {
        $user = new TelegramChatUserEntity();
        $user->chatId = -1001180970364;
        $user->userId = $userId;
        $user->username = 'testuser_' . $userId;
        $user->name = 'Test User';
        $user->isAdmin = false;
        $user->isBot = false;

        return $user;
    }

    private function createBan(): TelegramChatUserBanEntity
    {
        return TelegramChatUserBanEntity::create(
            -1001180970364,
            217708876,
            7816394199,
            12345
        );
    }

    private function createVoteEntity(?TelegramChatUserEntity $user): TelegramChatUserBanVoteEntity
    {
        $vote = new TelegramChatUserBanVoteEntity();
        $vote->user = $user;

        return $vote;
    }
}
