<?php

declare(strict_types=1);

namespace App\Application\Handler\Reaction;

use App\Application\Command\Telegram\Reaction\ReactionRemovedCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\Service\BanProcessServiceInterface;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Infrastructure\Telegram\Attribute\AsTelegramHandler;

#[AsTelegramHandler(ReactionRemovedCommand::class)]
final readonly class RemoveReactionHandler implements TelegramHandlerInterface
{
    public function __construct(
        private BanRepository $banRepository,
        private VoteRepository $voteRepository,
        private BanProcessServiceInterface $banProcessService,
        private ChatConfigServiceInterface $chatConfigService
    ) {
    }

    /**
     * @param ReactionRemovedCommand $command
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

        if ($reaction->chat === null) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        $ban = $this->banRepository->findBySpamMessage(
            (int) $reaction->chat->id,
            $reaction->message_id
        );
        if ($ban === null) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        if ($reaction->hasNewReaction()) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        $vote = $this->voteRepository->findByUserAndBan($command->user, $ban);
        if ($vote) {
            $this->voteRepository->delete($vote);
            $this->banProcessService->checkAndExecuteVerdict($command->chat, $ban);
        }

        return Messages::MESSAGE_BAN_STARTED;
    }
}
