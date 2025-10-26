<?php

declare(strict_types=1);

namespace App\Application\Handler\Reaction;

use App\Application\Command\Telegram\Reaction\StartBanByReactionCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\Service\BanService;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\Service\TrustService;
use App\Domain\Telegram\Service\VoteService;
use Psr\Log\LoggerInterface;

class StartBanByReactionHandler implements TelegramHandlerInterface
{
    public function __construct(
        private readonly BanRepository $banRepository,
        private readonly VoteRepository $voteRepository,
        private readonly UserRepository $userRepository,
        private readonly RequestHistoryRepository $requestHistoryRepository,
        private readonly TelegramApiService $telegramApiService,
        private readonly TrustService $trustService,
        private readonly ChatConfigServiceInterface $chatConfigService,
        private readonly VoteService $voteService,
        private readonly BanService $banService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param StartBanByReactionCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        if (!$command->chat->isEnabled) {
            return Messages::MESSAGE_BOT_DISABLED;
        }

        if (!$this->chatConfigService->isReactionsEnabled($command->chat)) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        $reaction = $command->update->message_reaction;
        if ($reaction === null) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        $emoji = $reaction->getNewEmoji();
        if ($emoji === null) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        $banEmoji = $this->chatConfigService->getBanEmoji($command->chat);
        if ($emoji !== $banEmoji) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        $history = $this->requestHistoryRepository->findMessageByReaction($reaction);
        if ($history === null) {
            $this->logger->warning('Message not found in history for reaction', [
                'chatId' => $reaction->chat->id,
                'messageId' => $reaction->message_id,
            ]);

            return Messages::MESSAGE_SPAM_404;
        }

        $spammerUserId = $history->fromId;

        $chatSpammer = $this->telegramApiService->getChatMember(
            $reaction->chat->id,
            $spammerUserId
        );

        if (!$chatSpammer || $chatSpammer->isAdmin()) {
            $this->logger->info('Admin cannot be banned via reaction', [
                'chatId' => $command->chat->chatId,
                'userId' => $spammerUserId,
            ]);

            return Messages::MESSAGE_ADMIN_IS_IMMUNE;
        }

        if ($this->trustService->isUserTrusted($command->chat, $spammerUserId)) {
            $this->logger->info('Trusted user cannot be banned via reaction', [
                'userId' => $spammerUserId,
                'chatId' => $command->chat->chatId,
            ]);

            return Messages::MESSAGE_USER_IS_TRUSTED;
        }

        $spammer = $this->userRepository->findByChatAndUser(
            $command->chat->chatId,
            $spammerUserId
        );

        if ($spammer === null) {
            $spammer = $this->userRepository->createUser(
                $command->chat->chatId,
                $spammerUserId
            );
            $spammer->name = $chatSpammer->user->getAlias();
            $this->userRepository->save($spammer);
        }

        $ban = $this->banRepository->createBan(
            $command->chat->chatId,
            $command->user->userId,
            $spammer->userId,
            0,
            $reaction->message_id
        );
        $this->banRepository->save($ban);

        $vote = $this->voteRepository->createVote(
            $command->user,
            $ban,
            $command->chat->chatId,
            VoteType::BAN
        );
        $this->voteRepository->save($vote);

        $voteResult = $this->voteService->getVoteResult($command->chat, $ban);

        if ($voteResult['shouldBan']) {
            $this->banService->banUser($command->chat, $ban);
        }

        return Messages::MESSAGE_BAN_STARTED;
    }
}
