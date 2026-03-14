<?php

declare(strict_types=1);

namespace App\Application\Handler\Ban;

use App\Application\Command\Telegram\Ban\StartBanCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\VoteType;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Service\BanMessageFormatterInterface;
use App\Domain\Telegram\Service\BanProcessServiceInterface;
use App\Infrastructure\Telegram\Attribute\AsTelegramHandler;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\SpammerMessageServiceInterface;
use App\Domain\Telegram\Service\TelegramChatMemberApiInterface;
use App\Domain\Telegram\Service\TelegramMessageApiInterface;
use App\Domain\Telegram\Service\TrustServiceInterface;
use App\Domain\Telegram\Service\UserPersisterInterface;
use App\Domain\Telegram\ValueObject\Bot\TelegramInlineKeyboard;
use App\Domain\Telegram\ValueObject\Bot\TelegramReplyMarkup;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use Psr\Log\LoggerInterface;
use Throwable;

#[AsTelegramHandler(StartBanCommand::class)]
final readonly class StartBanHandler implements TelegramHandlerInterface
{
    public function __construct(
        private BanRepository $banRepository,
        private BanProcessServiceInterface $banProcessService,
        private TelegramChatMemberApiInterface $chatMemberApi,
        private TelegramMessageApiInterface $messageApi,
        private SpammerMessageServiceInterface $spammerMessageService,
        private TrustServiceInterface $trustService,
        private ChatConfigServiceInterface $chatConfigService,
        private BanMessageFormatterInterface $messageFormatter,
        private UserPersisterInterface $userPersister,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param StartBanCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        if ($command->chat === null || !$command->chat->isEnabled) {
            return Messages::MESSAGE_BOT_DISABLED;
        }

        try {
            $spammerMessage = $this->spammerMessageService->getSpammerMessage($command->update);
        } catch (Throwable $e) {
            $this->logger->error('Failed to get spammer message', [
                'error' => $e->getMessage(),
                'update' => $command->update,
            ]);
            $spammerMessage = null;
        }

        if ($spammerMessage === null) {
            return Messages::MESSAGE_SPAM_404;
        }

        $chatSpammer = $this->chatMemberApi->getChatMember(
            $spammerMessage->chat->id,
            $spammerMessage->from->id
        );

        if (!$chatSpammer || $chatSpammer->isAdmin()) {
            return Messages::MESSAGE_ADMIN_IS_IMMUNE;
        }

        if ($command->user->userId === $spammerMessage->from->id) {
            return Messages::MESSAGE_NOT_SUPPORTED;
        }

        if ($chatSpammer->user->id && $this->trustService->isUserTrusted($command->chat, $chatSpammer->user->id)) {
            $this->logger->warning('Trusted user cannot be banned', [
                'userId' => $chatSpammer->user->id,
                'chatId' => $command->chat->chatId,
            ]);

            return Messages::MESSAGE_USER_IS_TRUSTED;
        }

        $spammer = $this->userPersister->persist(
            $command->update->getChat(),
            $spammerMessage->from
        );

        $existingBan = $this->banRepository->findBySpamMessage(
            $command->chat->chatId,
            $spammerMessage->message_id
        );

        if ($existingBan !== null) {
            return Messages::MESSAGE_BAN_ALREADY_STARTED;
        }

        $banMessage = $this->sendBanMessage($command, $spammer);
        if ($banMessage === null) {
            return Messages::MESSAGE_BAN_API_ERROR;
        }

        if ($banMessage->message_id !== null && $spammerMessage->from->id !== null) {
            $this->banProcessService->initiateBan(
                $command->chat,
                $command->user,
                $spammerMessage->from->id,
                $banMessage->message_id,
                $spammerMessage->message_id,
                $command->update->getMessageObj()->message_id ?? 0
            );
        }

        return Messages::MESSAGE_BAN_STARTED;
    }

    private function sendBanMessage(StartBanCommand $command, ?TelegramChatUserEntity $spammer): ?TelegramMessage
    {
        if ($command->user === null || $spammer === null) {
            return null;
        }

        $requiredVotes = $this->chatConfigService->getVotesRequired($command->chat);

        $texts = [
            $this->messageFormatter->formatStartBanMessage($command->user, $spammer),
            $this->messageFormatter->formatInitialVoteMessage(
                $command->user,
                VoteType::BAN
            ),
        ];

        $message = new TelegramSendMessage($command->chat->chatId, implode("\n", $texts));

        $keyboard = new TelegramInlineKeyboard();
        $keyboard->addButton(
            text: $this->messageFormatter->formatVoteButtonText(
                1,
                $requiredVotes,
                VoteType::BAN
            ),
            callbackData: VoteType::BAN->value
        );
        $keyboard->addButton(
            text: $this->messageFormatter->formatVoteButtonText(
                0,
                $requiredVotes,
                VoteType::FORGIVE
            ),
            callbackData: VoteType::FORGIVE->value
        );

        $replyMarkup = new TelegramReplyMarkup();
        $replyMarkup->inline_keyboard = $keyboard;
        $message->reply_markup = $replyMarkup;

        return $this->messageApi->sendMessage($message);
    }
}
