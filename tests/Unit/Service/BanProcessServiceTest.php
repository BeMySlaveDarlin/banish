<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Service\BanProcessService;
use App\Domain\Telegram\Service\BanServiceInterface;
use App\Domain\Telegram\Service\VoteServiceInterface;
use App\Domain\Telegram\ValueObject\VoteResult;
use App\Tests\Factory\EntityFactory;
use App\Tests\TestCase\AbstractUnitTestCase;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;

final class BanProcessServiceTest extends AbstractUnitTestCase
{
    /** @var BanRepository&Stub */
    private BanRepository $banRepository;
    /** @var VoteServiceInterface&Stub */
    private VoteServiceInterface $voteService;
    /** @var BanServiceInterface&Stub */
    private BanServiceInterface $banService;
    private BanProcessService $service;

    protected function setUp(): void
    {
        $this->banRepository = $this->createStub(BanRepository::class);
        $this->voteService = $this->createStub(VoteServiceInterface::class);
        $this->banService = $this->createStub(BanServiceInterface::class);

        $this->service = new BanProcessService(
            $this->banRepository,
            $this->voteService,
            $this->banService,
            new NullLogger(),
        );
    }

    public function testInitiateBanCreatesBanAndVote(): void
    {
        $chat = EntityFactory::createChat(-1001180970364);
        $reporter = EntityFactory::createUser(-1001180970364, 217708876);
        $spammerId = 7816394199;
        $banMessageId = 12345;

        $ban = EntityFactory::createBan(-1001180970364, $spammerId, 217708876);

        $banRepository = $this->createMock(BanRepository::class);
        $voteService = $this->createMock(VoteServiceInterface::class);

        $banRepository
            ->expects(self::once())
            ->method('createBan')
            ->with(-1001180970364, 217708876, $spammerId, $banMessageId, null, null)
            ->willReturn($ban);

        $banRepository->expects(self::once())->method('save')->with($ban);

        $voteService
            ->expects(self::once())
            ->method('vote')
            ->with($chat, $reporter, $ban, VoteType::BAN);

        $service = new BanProcessService(
            $banRepository,
            $voteService,
            $this->banService,
            new NullLogger(),
        );

        $result = $service->initiateBan($chat, $reporter, $spammerId, $banMessageId);

        self::assertSame($ban, $result);
    }

    public function testInitiateBanSavesBanInRepository(): void
    {
        $chat = EntityFactory::createChat(-1001180970364);
        $reporter = EntityFactory::createUser(-1001180970364, 217708876);
        $ban = EntityFactory::createBan(-1001180970364, 999, 217708876);

        $banRepository = $this->createMock(BanRepository::class);
        $banRepository->method('createBan')->willReturn($ban);
        $banRepository
            ->expects(self::once())
            ->method('save')
            ->with($ban);

        $service = new BanProcessService(
            $banRepository,
            $this->voteService,
            $this->banService,
            new NullLogger(),
        );

        $service->initiateBan($chat, $reporter, 999, 55555, 100, 200);
    }

    public function testProcessVoteCallsVoteAndCheckVerdict(): void
    {
        $chat = EntityFactory::createChat(-1001180970364);
        $user = EntityFactory::createUser(-1001180970364, 217708876);
        $ban = EntityFactory::createBan(-1001180970364, 999, 111);

        $voteResult = $this->createVoteResult(shouldBan: false, shouldForgive: false);

        $voteService = $this->createMock(VoteServiceInterface::class);

        $voteService
            ->expects(self::once())
            ->method('vote')
            ->with($chat, $user, $ban, VoteType::BAN);

        $voteService
            ->expects(self::once())
            ->method('getVoteResult')
            ->with($chat, $ban)
            ->willReturn($voteResult);

        $service = new BanProcessService(
            $this->banRepository,
            $voteService,
            $this->banService,
            new NullLogger(),
        );

        $result = $service->processVote($chat, $user, $ban, VoteType::BAN);

        self::assertSame($voteResult, $result);
    }

    public function testProcessVoteReturnsVoteResult(): void
    {
        $chat = EntityFactory::createChat(-1001180970364);
        $user = EntityFactory::createUser(-1001180970364, 217708876);
        $ban = EntityFactory::createBan(-1001180970364, 999, 111);

        $voteResult = $this->createVoteResult(shouldBan: true, shouldForgive: false, upCount: 3);

        $this->voteService->method('getVoteResult')->willReturn($voteResult);
        $this->banService->method('banUser');

        $result = $this->service->processVote($chat, $user, $ban, VoteType::BAN);

        self::assertSame(3, $result->upCount);
        self::assertTrue($result->shouldBan);
    }

    public function testCheckAndExecuteVerdictBansUserWhenShouldBan(): void
    {
        $chat = EntityFactory::createChat(-1001180970364);
        $ban = EntityFactory::createBan(-1001180970364, 999, 111);

        $voteResult = $this->createVoteResult(shouldBan: true, shouldForgive: false);

        $this->voteService->method('getVoteResult')->willReturn($voteResult);

        $banService = $this->createMock(BanServiceInterface::class);

        $banService
            ->expects(self::once())
            ->method('banUser')
            ->with($chat, $ban);

        $banService->expects(self::never())->method('forgiveBan');

        $service = new BanProcessService(
            $this->banRepository,
            $this->voteService,
            $banService,
            new NullLogger(),
        );

        $service->checkAndExecuteVerdict($chat, $ban);
    }

    public function testCheckAndExecuteVerdictForgivesWhenShouldForgive(): void
    {
        $chat = EntityFactory::createChat(-1001180970364);
        $ban = EntityFactory::createBan(-1001180970364, 999, 111);

        $voteResult = $this->createVoteResult(shouldBan: false, shouldForgive: true);

        $this->voteService->method('getVoteResult')->willReturn($voteResult);

        $banService = $this->createMock(BanServiceInterface::class);

        $banService->expects(self::never())->method('banUser');

        $banService
            ->expects(self::once())
            ->method('forgiveBan')
            ->with($ban);

        $service = new BanProcessService(
            $this->banRepository,
            $this->voteService,
            $banService,
            new NullLogger(),
        );

        $service->checkAndExecuteVerdict($chat, $ban);
    }

    public function testCheckAndExecuteVerdictNoActionWhenNoVerdict(): void
    {
        $chat = EntityFactory::createChat(-1001180970364);
        $ban = EntityFactory::createBan(-1001180970364, 999, 111);

        $voteResult = $this->createVoteResult(shouldBan: false, shouldForgive: false);

        $this->voteService->method('getVoteResult')->willReturn($voteResult);

        $banService = $this->createMock(BanServiceInterface::class);
        $banService->expects(self::never())->method('banUser');
        $banService->expects(self::never())->method('forgiveBan');

        $service = new BanProcessService(
            $this->banRepository,
            $this->voteService,
            $banService,
            new NullLogger(),
        );

        $result = $service->checkAndExecuteVerdict($chat, $ban);

        self::assertFalse($result->shouldBan);
        self::assertFalse($result->shouldForgive);
    }

    public function testCheckAndExecuteVerdictCatchesOptimisticLockException(): void
    {
        $chat = EntityFactory::createChat(-1001180970364);
        $ban = EntityFactory::createBan(-1001180970364, 999, 111);
        $ref = new \ReflectionProperty(TelegramChatUserBanEntity::class, 'id');
        $ref->setValue($ban, '42');

        $voteResult = $this->createVoteResult(shouldBan: true, shouldForgive: false);

        $this->voteService->method('getVoteResult')->willReturn($voteResult);

        $this->banService
            ->method('banUser')
            ->willThrowException(OptimisticLockException::lockFailedVersionMismatch($ban, 2, 1));

        $result = $this->service->checkAndExecuteVerdict($chat, $ban);

        self::assertSame($voteResult, $result);
    }

    private function createVoteResult(
        bool $shouldBan = false,
        bool $shouldForgive = false,
        int $upCount = 1,
        int $downCount = 0,
        int $requiredVotes = 3,
    ): VoteResult {
        return new VoteResult(
            upVotes: [],
            downVotes: [],
            upCount: $upCount,
            downCount: $downCount,
            requiredVotes: $requiredVotes,
            shouldBan: $shouldBan,
            shouldForgive: $shouldForgive,
        );
    }
}
