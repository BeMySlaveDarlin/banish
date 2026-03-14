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
use App\Domain\Telegram\Service\BanProcessServiceInterface;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\TelegramChatMemberApiInterface;
use App\Domain\Telegram\Service\TrustServiceInterface;
use App\Domain\Telegram\Service\UserPersisterInterface;
use App\Infrastructure\Telegram\Attribute\AsTelegramHandler;
use Psr\Log\LoggerInterface;

#[AsTelegramHandler(StartBanByReactionCommand::class)]
final readonly class StartBanByReactionHandler implements TelegramHandlerInterface
{
    public function __construct(
        private BanRepository $banRepository,
        private RequestHistoryRepository $requestHistoryRepository,
        private BanProcessServiceInterface $banProcessService,
        private TelegramChatMemberApiInterface $chatMemberApi,
        private TrustServiceInterface $trustService,
        private ChatConfigServiceInterface $chatConfigService,
        private UserPersisterInterface $userPersister,
        private LoggerInterface $logger
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

        $existingBan = $this->banRepository->findBySpamMessage(
            $command->chat->chatId,
            $reaction->message_id
        );

        if ($existingBan !== null) {
            $voteType = $this->resolveVoteType($command, $emoji);
            if ($voteType === null) {
                return Messages::MESSAGE_NOT_SUPPORTED;
            }

            $this->banProcessService->processVote($command->chat, $command->user, $existingBan, $voteType);

            return Messages::MESSAGE_BAN_PROCESSED;
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

        $chatSpammer = $this->chatMemberApi->getChatMember(
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
            $this->logger->warning('Trusted user cannot be banned via reaction', [
                'userId' => $spammerUserId,
                'chatId' => $command->chat->chatId,
            ]);

            return Messages::MESSAGE_USER_IS_TRUSTED;
        }

        if ($command->user->userId === $spammerUserId) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        if (!$command->user->isAdmin && !$this->trustService->isUserTrusted($command->chat, $command->user->userId)) {
            $this->logger->info('Non-trusted user cannot initiate ban via reaction', [
                'userId' => $command->user->userId,
                'chatId' => $command->chat->chatId,
            ]);

            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        $spammer = $this->userPersister->persist(
            $command->update->getChat(),
            $chatSpammer->user
        );

        $ban = $this->banProcessService->initiateBan(
            $command->chat,
            $command->user,
            $spammer->userId,
            0,
            $reaction->message_id
        );

        $this->banProcessService->checkAndExecuteVerdict($command->chat, $ban);

        return Messages::MESSAGE_BAN_STARTED;
    }

    private function resolveVoteType(StartBanByReactionCommand $command, string $emoji): ?VoteType
    {
        $banEmoji = $this->chatConfigService->getBanEmoji($command->chat);
        $forgiveEmoji = $this->chatConfigService->getForgiveEmoji($command->chat);

        if ($emoji === $banEmoji) {
            return VoteType::BAN;
        }

        if ($emoji === $forgiveEmoji) {
            return VoteType::FORGIVE;
        }

        return null;
    }
}
