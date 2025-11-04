<?php

declare(strict_types=1);

namespace App\Application\Handler\Ban;

use App\Application\Command\Telegram\Ban\VoteForBanCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\BanMessageFormatter;
use App\Domain\Telegram\Service\BanService;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\Service\VoteService;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramInlineKeyboard;
use App\Domain\Telegram\ValueObject\Bot\TelegramReplyMarkup;

class VoteForBanHandler implements TelegramHandlerInterface
{
    public function __construct(
        private readonly BanRepository $banRepository,
        private readonly ChatConfigServiceInterface $chatConfigService,
        private readonly UserRepository $userRepository,
        private readonly VoteService $voteService,
        private readonly BanService $banService,
        private readonly BanMessageFormatter $messageFormatter,
        private readonly TelegramApiService $telegramApiService
    ) {
    }

    /**
     * @param VoteForBanCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        if ($command->chat === null || !$command->chat->isEnabled) {
            return Messages::MESSAGE_BOT_DISABLED;
        }

        $supportedVotes = [
            VoteType::BAN->value,
            VoteType::FORGIVE->value,
        ];

        $voteType = (string) ($command->update->callback_query?->data ?? '');
        if (!in_array($voteType, $supportedVotes, true)) {
            return Messages::MESSAGE_NOT_SUPPORTED_CB;
        }

        $messageId = $command->update->getMessageObj()->message_id;
        if ($messageId === null) {
            return Messages::MESSAGE_BAN_404;
        }

        $ban = $this->banRepository->findActiveBan(
            $command->chat->chatId,
            $messageId
        );

        if ($ban === null) {
            return Messages::MESSAGE_BAN_404;
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

        return Messages::MESSAGE_BAN_PROCESSED;
    }

    /**
     * @param array<string, mixed> $voteResult
     */
    private function updateBanMessage(VoteForBanCommand $command, TelegramChatUserBanEntity $ban, array $voteResult): void
    {
        if ($command->chat === null) {
            return;
        }

        $reporter = $this->userRepository->findByChatAndUser(
            $command->chat->chatId,
            $ban->reporterId
        );

        $spammer = $this->userRepository->findByChatAndUser(
            $command->chat->chatId,
            $ban->spammerId
        );

        /** @var array<int, TelegramChatUserEntity> $upVotes */
        $upVotes = $voteResult['upVotes'] ?? [];
        /** @var array<int, TelegramChatUserEntity> $downVotes */
        $downVotes = $voteResult['downVotes'] ?? [];

        $deleteOnlyMessage = $this->chatConfigService->isDeleteOnlyEnabled($command->chat);
        $text = $this->messageFormatter->formatVoteMessage(
            $ban,
            $reporter,
            $spammer,
            $upVotes,
            $downVotes,
            $deleteOnlyMessage
        );

        $replyMarkup = new TelegramReplyMarkup();
        $upCount = is_int($voteResult['upCount'] ?? null) ? $voteResult['upCount'] : 0;
        $downCount = is_int($voteResult['downCount'] ?? null) ? $voteResult['downCount'] : 0;
        $requiredVotes = is_int($voteResult['requiredVotes'] ?? null) ? $voteResult['requiredVotes'] : 0;

        if ($ban->isPending()) {
            $keyboard = new TelegramInlineKeyboard();
            $keyboard->addButton(
                text: $this->messageFormatter->formatVoteButtonText(
                    $upCount,
                    $requiredVotes,
                    VoteType::BAN
                ),
                callbackData: VoteType::BAN->value
            );
            $keyboard->addButton(
                text: $this->messageFormatter->formatVoteButtonText(
                    $downCount,
                    $requiredVotes,
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
