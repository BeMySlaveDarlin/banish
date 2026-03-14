<?php

declare(strict_types=1);

namespace App\Application\Handler\Reaction;

use App\Application\Command\Telegram\Reaction\VoteByReactionCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\Service\BanProcessServiceInterface;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Infrastructure\Telegram\Attribute\AsTelegramHandler;

#[AsTelegramHandler(VoteByReactionCommand::class)]
final readonly class VoteByReactionHandler implements TelegramHandlerInterface
{
    public function __construct(
        private BanRepository $banRepository,
        private VoteRepository $voteRepository,
        private BanProcessServiceInterface $banProcessService,
        private ChatConfigServiceInterface $chatConfigService,
    ) {
    }

    /**
     * @param VoteByReactionCommand $command
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

        $ban = $this->banRepository->findBySpamMessage(
            $command->chat->chatId,
            $reaction->message_id
        );

        if ($ban === null) {
            return Messages::MESSAGE_BAN_404;
        }

        if (!$reaction->hasNewReaction()) {
            $vote = $this->voteRepository->findByUserAndBan($command->user, $ban);
            if ($vote) {
                $this->voteRepository->delete($vote);
                $this->banProcessService->checkAndExecuteVerdict($command->chat, $ban);
            }

            return Messages::MESSAGE_BAN_PROCESSED;
        }

        $emoji = $reaction->getNewEmoji();
        if ($emoji === null) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        $voteType = $this->getVoteTypeFromEmoji($command, $emoji);
        if ($voteType === null) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        $this->banProcessService->processVote($command->chat, $command->user, $ban, $voteType);

        return Messages::MESSAGE_BAN_PROCESSED;
    }

    private function getVoteTypeFromEmoji(VoteByReactionCommand $command, string $emoji): ?VoteType
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
