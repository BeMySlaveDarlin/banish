<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatEntity;
use App\Component\Telegram\Entity\TelegramChatUserBanEntity;
use App\Component\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\ValueObject\Bot\TelegramEditMessage;
use App\Component\Telegram\ValueObject\Bot\TelegramInlineKeyboard;
use App\Component\Telegram\ValueObject\Bot\TelegramReplyMarkup;
use App\Component\Telegram\ValueObject\ResponseMessages;
use App\Component\Telegram\ValueObject\TelegramMessage;

readonly class TelegramBanVoteStartProcedureUseCase extends TelegramBanStartProcedureUseCase
{
    public function handleUpdate(TelegramChatEntity $chat, TelegramChatUserEntity $user): string
    {
        if (!$chat->isEnabled) {
            return ResponseMessages::MESSAGE_BOT_DISABLED;
        }

        $supportedCallbackData = [TelegramChatUserBanVoteEntity::TYPE_DO_BAN, TelegramChatUserBanVoteEntity::TYPE_FORGIVE];
        if (!in_array((string) $this->update->callback_query_data, $supportedCallbackData, true)) {
            return ResponseMessages::MESSAGE_NOT_SUPPORTED_CB;
        }

        $userBan = $this->getUserBan($chat, $this->update->message);
        if (null === $userBan) {
            return ResponseMessages::MESSAGE_BAN_404;
        }

        $this->findOrCreateUserBanVote($chat, $user, $userBan);

        $upVotes = [];
        $downVotes = [];
        $limitVotes = $chat->options->get(TelegramChatEntity::OPTION_BAN_VOTES_REQUIRED) ?? TelegramChatEntity::DEFAULT_VOTES_REQUIRED;
        foreach ($userBan->votes as $vote) {
            if ($vote->vote === TelegramChatUserBanVoteEntity::TYPE_DO_BAN) {
                $upVotes[] = $vote->user->getAlias();
            }
            if ($vote->vote === TelegramChatUserBanVoteEntity::TYPE_FORGIVE) {
                $downVotes[] = $vote->user->getAlias();
            }
        }

        if (count($upVotes) >= $limitVotes) {
            $userBan->status = TelegramChatUserBanEntity::STATUS_BANNED;
            $this->banUserAndDeleteSpamMessage($chat, $userBan);
        }
        if (count($downVotes) >= $limitVotes) {
            $userBan->status = TelegramChatUserBanEntity::STATUS_CANCELED;
        }

        $this->sendVoteMessage($chat, $userBan, $upVotes, $downVotes, $limitVotes);

        $this->entityManager->persist($userBan);
        $this->entityManager->flush();

        return ResponseMessages::MESSAGE_BAN_PROCESSED;
    }

    private function sendVoteMessage(
        TelegramChatEntity $chat,
        TelegramChatUserBanEntity $userBan,
        array $upVotes,
        array $downVotes,
        int $limitVotes
    ): void {
        $reporter = $this->entityManager
            ->getRepository(TelegramChatUserEntity::class)
            ->findOneBy([
                'chatId' => $chat->chatId,
                'userId' => $userBan->reporterId,
            ]);

        $spammer = $this->entityManager
            ->getRepository(TelegramChatUserEntity::class)
            ->findOneBy([
                'chatId' => $chat->chatId,
                'userId' => $userBan->spammerId,
            ]);

        $text = $this->getText($userBan, $reporter, $spammer, $upVotes, $downVotes);
        $data = new TelegramEditMessage($chat->chatId, $userBan->banMessageId, $text);
        $this->apiClientPolicy->editMessageText($data);

        $replyMarkup = new TelegramReplyMarkup();
        $data->reply_markup = $replyMarkup;

        if ($userBan->isPending()) {
            $keyboard = new TelegramInlineKeyboard();
            $keyboard->addButton(
                text: sprintf(ResponseMessages::VOTE_BAN_BUTTON_PATTERN, count($upVotes), $limitVotes),
                callbackData: TelegramChatUserBanVoteEntity::TYPE_DO_BAN
            );
            $keyboard->addButton(
                text: sprintf(ResponseMessages::VOTE_FORGIVE_BUTTON_PATTERN, count($downVotes), $limitVotes),
                callbackData: TelegramChatUserBanVoteEntity::TYPE_FORGIVE
            );
            $replyMarkup->inline_keyboard = $keyboard;
        }

        $this->apiClientPolicy->editMessageKb($data);
    }

    private function banUserAndDeleteSpamMessage(
        TelegramChatEntity $chat,
        TelegramChatUserBanEntity $userBan
    ): void {
        $this->apiClientPolicy->banChatMember($chat->chatId, $userBan->spammerId);

        $deleteMessage = $chat->options->get(TelegramChatEntity::OPTION_DELETE_MESSAGE) ?? TelegramChatEntity::DEFAULT_DELETE_MESSAGES;
        if (!$deleteMessage) {
            return;
        }

        $this->apiClientPolicy->deleteMessage($chat->chatId, $userBan->spamMessageId);
    }

    private function getText(
        TelegramChatUserBanEntity $userBan,
        ?TelegramChatUserEntity $reporter = null,
        ?TelegramChatUserEntity $spammer = null,
        array $upVotes = [],
        array $downVotes = [],
    ): string {
        $texts = [
            sprintf(
                ResponseMessages::START_BAN_PATTERN,
                $reporter?->getAlias(),
                $spammer?->getAlias()
            ),
        ];
        if (!empty($upVotes)) {
            $texts[] = $this->getVotersText(
                TelegramChatUserBanVoteEntity::TYPE_DO_BAN . ' ' . ResponseMessages::EMOJI_BAN,
                $upVotes
            );
        }
        if (!empty($downVotes)) {
            $texts[] = $this->getVotersText(
                TelegramChatUserBanVoteEntity::TYPE_FORGIVE . ' ' . ResponseMessages::EMOJI_FORGIVE,
                $downVotes
            );
        }
        if (!$userBan->isPending()) {
            $texts[] = sprintf("%s is %s", $spammer?->getAlias(), $userBan->isBanned() ? ' banned' : 'not banned');
        }

        return implode("\n", $texts);
    }

    private function getVotersText(string $vote, array $votes = []): string
    {
        $voters = implode(' ', $votes);

        return sprintf(ResponseMessages::VOTE_BAN_PATTERN, $voters, $vote);
    }

    private function getUserBan(
        TelegramChatEntity $chat,
        TelegramMessage $banMessage
    ): ?TelegramChatUserBanEntity {
        return $this->entityManager
            ->getRepository(TelegramChatUserBanEntity::class)
            ->findOneBy([
                'chatId' => $chat->chatId,
                'banMessageId' => $banMessage->message_id,
                'status' => TelegramChatUserBanEntity::STATUS_PENDING,
            ]);
    }
}
