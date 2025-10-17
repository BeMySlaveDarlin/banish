<?php

declare(strict_types=1);

namespace App\Application\Handler\Ban;

use App\Application\Command\Telegram\Ban\VoteForBanCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\BanMessageFormatter;
use App\Domain\Telegram\Service\BanService;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\Service\VoteService;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramInlineKeyboard;
use App\Domain\Telegram\ValueObject\Bot\TelegramReplyMarkup;
use App\Domain\Telegram\ValueObject\ResponseMessages;

class VoteForBanHandler implements TelegramHandlerInterface
{
    public function __construct(
        private BanRepository $banRepository,
        private UserRepository $userRepository,
        private VoteService $voteService,
        private BanService $banService,
        private BanMessageFormatter $messageFormatter,
        private TelegramApiService $telegramApiService
    ) {
    }

    /**
     * @param VoteForBanCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        if (!$command->chat->isEnabled) {
            return ResponseMessages::MESSAGE_BOT_DISABLED;
        }

        $supportedVotes = [
            VoteType::BAN->value,
            VoteType::FORGIVE->value,
        ];

        $voteType = (string) $command->update->callback_query->data;
        if (!in_array($voteType, $supportedVotes, true)) {
            return ResponseMessages::MESSAGE_NOT_SUPPORTED_CB;
        }

        $ban = $this->banRepository->findActiveBan(
            $command->chat->chatId,
            $command->update->getMessageObj()->message_id
        );

        if ($ban === null) {
            return ResponseMessages::MESSAGE_BAN_404;
        }

        $this->voteService->vote($command->chat, $command->user, $ban, VoteType::from($voteType));

        $voteResult = $this->voteService->getVoteResult($command->chat, $ban);

        if ($voteResult['shouldBan']) {
            $this->banService->banUser($command->chat, $ban);
        }

        if ($voteResult['shouldForgive']) {
            $this->banService->forgiveBan($ban);
        }

        $this->updateBanMessage($command, $ban, $voteResult);

        return ResponseMessages::MESSAGE_BAN_PROCESSED;
    }

    private function updateBanMessage(VoteForBanCommand $command, $ban, array $voteResult): void
    {
        $reporter = $this->userRepository->findByChatAndUser(
            $command->chat->chatId,
            $ban->reporterId
        );

        $spammer = $this->userRepository->findByChatAndUser(
            $command->chat->chatId,
            $ban->spammerId
        );

        $text = $this->messageFormatter->formatVoteMessage(
            $ban,
            $reporter,
            $spammer,
            $voteResult['upVotes'],
            $voteResult['downVotes']
        );

        $replyMarkup = new TelegramReplyMarkup();

        if ($ban->isPending()) {
            $keyboard = new TelegramInlineKeyboard();
            $keyboard->addButton(
                text: $this->messageFormatter->formatVoteButtonText(
                    $voteResult['upCount'],
                    $voteResult['requiredVotes'],
                    VoteType::BAN
                ),
                callbackData: VoteType::BAN->value
            );
            $keyboard->addButton(
                text: $this->messageFormatter->formatVoteButtonText(
                    $voteResult['downCount'],
                    $voteResult['requiredVotes'],
                    VoteType::FORGIVE
                ),
                callbackData: VoteType::FORGIVE->value
            );
            $replyMarkup->inline_keyboard = $keyboard;
        }

        $editMessage = new TelegramEditMessage(
            $command->chat->chatId,
            $ban->banMessageId,
            $text,
            $replyMarkup
        );
        $this->telegramApiService->editMessageText($editMessage);
    }
}
