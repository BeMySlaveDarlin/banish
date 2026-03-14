<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\ValueObject\VoteResult;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;

final readonly class BanProcessService implements BanProcessServiceInterface
{
    public function __construct(
        private BanRepository $banRepository,
        private VoteServiceInterface $voteService,
        private BanServiceInterface $banService,
        private LoggerInterface $logger
    ) {
    }

    public function initiateBan(
        TelegramChatEntity $chat,
        TelegramChatUserEntity $reporter,
        int $spammerId,
        int $banMessageId,
        ?int $spamMessageId = null,
        ?int $initialMessageId = null
    ): TelegramChatUserBanEntity {
        $ban = $this->banRepository->createBan(
            $chat->chatId,
            $reporter->userId,
            $spammerId,
            $banMessageId,
            $spamMessageId,
            $initialMessageId
        );
        $this->banRepository->save($ban);

        $this->voteService->vote($chat, $reporter, $ban, VoteType::BAN);

        return $ban;
    }

    public function processVote(
        TelegramChatEntity $chat,
        TelegramChatUserEntity $user,
        TelegramChatUserBanEntity $ban,
        VoteType $voteType
    ): VoteResult {
        $this->voteService->vote($chat, $user, $ban, $voteType);

        return $this->checkAndExecuteVerdict($chat, $ban);
    }

    public function checkAndExecuteVerdict(
        TelegramChatEntity $chat,
        TelegramChatUserBanEntity $ban
    ): VoteResult {
        $voteResult = $this->voteService->getVoteResult($chat, $ban);

        try {
            if ($voteResult->shouldBan) {
                $this->banService->banUser($chat, $ban);
            }

            if ($voteResult->shouldForgive) {
                $this->banService->forgiveBan($ban);
            }
        } catch (OptimisticLockException $e) {
            $this->logger->warning('Optimistic lock conflict in checkAndExecuteVerdict, skipping', [
                'banId' => $ban->id,
                'chatId' => $chat->chatId,
            ]);
        }

        return $voteResult;
    }
}
