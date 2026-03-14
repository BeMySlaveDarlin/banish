<?php

declare(strict_types=1);

namespace App\Application\Handler\Ban;

use App\Application\Command\Telegram\Ban\VoteForBanCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\BanMessageFormatterInterface;
use App\Domain\Telegram\Service\BanProcessServiceInterface;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\TelegramMessageApiInterface;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramInlineKeyboard;
use App\Domain\Telegram\ValueObject\Bot\TelegramReplyMarkup;
use App\Domain\Telegram\ValueObject\VoteResult;
use App\Infrastructure\Telegram\Attribute\AsTelegramHandler;

#[AsTelegramHandler(VoteForBanCommand::class)]
final readonly class VoteForBanHandler implements TelegramHandlerInterface
{
    public function __construct(
        private BanRepository $banRepository,
        private ChatConfigServiceInterface $chatConfigService,
        private UserRepository $userRepository,
        private BanProcessServiceInterface $banProcessService,
        private BanMessageFormatterInterface $messageFormatter,
        private TelegramMessageApiInterface $messageApi
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

        $voteResult = $this->banProcessService->processVote(
            $command->chat,
            $command->user,
            $ban,
            VoteType::from($voteType)
        );

        $this->updateBanMessage($command, $ban, $voteResult);

        return Messages::MESSAGE_BAN_PROCESSED;
    }

    private function updateBanMessage(VoteForBanCommand $command, TelegramChatUserBanEntity $ban, VoteResult $voteResult): void
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

        $deleteOnlyMessage = $this->chatConfigService->isDeleteOnlyEnabled($command->chat);
        $text = $this->messageFormatter->formatVoteMessage(
            $ban,
            $reporter,
            $spammer,
            $voteResult->upVotes,
            $voteResult->downVotes,
            $deleteOnlyMessage
        );

        $replyMarkup = new TelegramReplyMarkup();

        if ($ban->isPending()) {
            $keyboard = new TelegramInlineKeyboard();
            $keyboard->addButton(
                text: $this->messageFormatter->formatVoteButtonText(
                    $voteResult->upCount,
                    $voteResult->requiredVotes,
                    VoteType::BAN
                ),
                callbackData: VoteType::BAN->value
            );
            $keyboard->addButton(
                text: $this->messageFormatter->formatVoteButtonText(
                    $voteResult->downCount,
                    $voteResult->requiredVotes,
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

        $this->messageApi->editMessageText($editMessage);
    }
}
