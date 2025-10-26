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
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Repository\VoteRepository;
use App\Domain\Telegram\Service\BanMessageFormatter;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\Service\SpammerMessageService;
use App\Domain\Telegram\Service\TelegramApiService;
use App\Domain\Telegram\Service\TrustService;
use App\Domain\Telegram\ValueObject\Bot\TelegramInlineKeyboard;
use App\Domain\Telegram\ValueObject\Bot\TelegramReplyMarkup;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use Psr\Log\LoggerInterface;
use Throwable;

class StartBanHandler implements TelegramHandlerInterface
{
    public function __construct(
        private readonly BanRepository $banRepository,
        private readonly VoteRepository $voteRepository,
        private readonly UserRepository $userRepository,
        private readonly TelegramApiService $telegramApiService,
        private readonly SpammerMessageService $spammerMessageService,
        private readonly TrustService $trustService,
        private readonly ChatConfigServiceInterface $chatConfigService,
        private readonly BanMessageFormatter $messageFormatter,
        private readonly LoggerInterface $logger
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

        $chatSpammer = $this->telegramApiService->getChatMember(
            $spammerMessage->chat->id,
            $spammerMessage->from->id
        );

        if (!$chatSpammer || $chatSpammer->isAdmin()) {
            return Messages::MESSAGE_ADMIN_IS_IMMUNE;
        }

        if ($chatSpammer->user->id && $this->trustService->isUserTrusted($command->chat, $chatSpammer->user->id)) {
            $this->logger->warning('Trusted user cannot be banned', [
                'userId' => $chatSpammer->user->id,
                'chatId' => $command->chat->chatId,
            ]);

            return Messages::MESSAGE_USER_IS_TRUSTED;
        }

        $spammer = $this->userRepository->findByChatAndUser(
            $command->chat->chatId,
            $spammerMessage->from->id
        );

        if ($spammer === null && $spammerMessage->from->id !== null) {
            $spammer = $this->userRepository->createUser(
                $command->chat->chatId,
                $spammerMessage->from->id
            );
            $spammer->name = $spammerMessage->from->getAlias();
            $this->userRepository->save($spammer);
        }

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

        $ban = $this->banRepository->findByReporterAndMessage(
            $command->chat->chatId,
            $command->user->userId,
            $banMessage->message_id
        );

        if ($ban === null && $banMessage->message_id !== null && $spammerMessage->from->id !== null) {
            $ban = $this->banRepository->createBan(
                $command->chat->chatId,
                $command->user->userId,
                $spammerMessage->from->id,
                $banMessage->message_id,
                $spammerMessage->message_id,
                $command->update->getMessageObj()->message_id ?? 0
            );
            $this->banRepository->save($ban);
        }

        if ($ban !== null && $command->user !== null) {
            $vote = $this->voteRepository->findByUserAndBan($command->user, $ban);
            if ($vote === null) {
                $vote = $this->voteRepository->createVote(
                    $command->user,
                    $ban,
                    $command->chat->chatId,
                    VoteType::BAN
                );
                $this->voteRepository->save($vote);
            }
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

        return $this->telegramApiService->sendMessage($message);
    }
}
