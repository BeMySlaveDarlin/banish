<?php

declare(strict_types=1);

namespace App\Application\Handler\Reaction;

use App\Application\Command\Telegram\Reaction\ReactionRemovedCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\Service\BanService;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\VoteService;

class RemoveReactionHandler implements TelegramHandlerInterface
{
    public function __construct(
        private readonly BanRepository $banRepository,
        private readonly VoteRepository $voteRepository,
        private readonly VoteService $voteService,
        private readonly BanService $banService,
        private readonly ChatConfigServiceInterface $chatConfigService
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

            $voteResult = $this->voteService->getVoteResult($command->chat, $ban);
            if ($voteResult['shouldForgive']) {
                $this->banService->forgiveBan($ban);
            }
        }

        return Messages::MESSAGE_BAN_STARTED;
    }
}
