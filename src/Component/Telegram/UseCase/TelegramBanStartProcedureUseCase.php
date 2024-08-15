<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatEntity;
use App\Component\Telegram\Entity\TelegramChatUserBanEntity;
use App\Component\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\Entity\TelegramRequestHistoryEntity;
use App\Component\Telegram\ValueObject\Bot\TelegramInlineKeyboard;
use App\Component\Telegram\ValueObject\Bot\TelegramReplyMarkup;
use App\Component\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Component\Telegram\ValueObject\ResponseMessages;
use App\Component\Telegram\ValueObject\TelegramMessage;
use App\Component\Telegram\ValueObject\TelegramUpdate;
use Throwable;

readonly class TelegramBanStartProcedureUseCase extends AbstractTelegramUseCase
{
    public function handleUpdate(TelegramChatEntity $chat, TelegramChatUserEntity $user): string
    {
        if (!$chat->isEnabled) {
            return ResponseMessages::MESSAGE_BOT_DISABLED;
        }

        $spammerMessage = $this->getSpammerMessage();
        if (null === $spammerMessage) {
            return ResponseMessages::MESSAGE_SPAM_404;
        }

        $chatSpammer = $this->apiClientPolicy->getChatMember(
            $spammerMessage->chat->id,
            $spammerMessage->from->id
        );
        if (!$chatSpammer || $chatSpammer->isAdmin()) {
            return ResponseMessages::MESSAGE_ADMIN_IS_IMMUNE;
        }

        $banMessage = $this->sendBanMessage($chat, $user, $spammerMessage);
        if (null === $banMessage) {
            return ResponseMessages::MESSAGE_BAN_API_ERROR;
        }

        $userBan = $this->findOrCreateUserBan($chat, $user, $spammerMessage, $banMessage);
        $this->findOrCreateUserBanVote($chat, $user, $userBan);

        return ResponseMessages::MESSAGE_BAN_STARTED;
    }

    private function sendBanMessage(TelegramChatEntity $chat, TelegramChatUserEntity $user, TelegramMessage $spammerMessage): ?TelegramMessage
    {
        $limitVotes = $chat->options->get(TelegramChatEntity::OPTION_BAN_VOTES_REQUIRED) ?? TelegramChatEntity::DEFAULT_VOTES_REQUIRED;
        $texts = [
            sprintf(
                ResponseMessages::START_BAN_PATTERN,
                $user->getAlias(),
                $spammerMessage->from->getAlias()
            ),
            sprintf(
                ResponseMessages::VOTE_BAN_PATTERN,
                $user->getAlias(),
                TelegramChatUserBanVoteEntity::TYPE_DO_BAN . ' ' . ResponseMessages::EMOJI_BAN
            ),
        ];

        $data = new TelegramSendMessage($chat->chatId, implode("\n", $texts));
        $keyboard = new TelegramInlineKeyboard();
        $keyboard->addButton(
            text: sprintf(ResponseMessages::VOTE_BAN_BUTTON_PATTERN, 1, $limitVotes),
            callbackData: TelegramChatUserBanVoteEntity::TYPE_DO_BAN
        );
        $keyboard->addButton(
            text: sprintf(ResponseMessages::VOTE_FORGIVE_BUTTON_PATTERN, 0, $limitVotes),
            callbackData: TelegramChatUserBanVoteEntity::TYPE_FORGIVE
        );
        $replyMarkup = new TelegramReplyMarkup();
        $replyMarkup->inline_keyboard = $keyboard;
        $data->reply_markup = $replyMarkup;

        return $this->apiClientPolicy->sendMessage($data);
    }

    private function getSpammerMessage(): ?TelegramMessage
    {
        try {
            if ($this->update->getMessageObj()->hasReply()) {
                return $this->update->getMessageObj()->reply_to_message;
            }

            $prevHistory = $this->entityManager
                ->getRepository(TelegramRequestHistoryEntity::class)
                ->findPreviousMessage(
                    $this->update->getChat()->id,
                    $this->update->getFrom()->id,
                    $this->update->getMessageObj()->message_id
                );

            if ($prevHistory === null) {
                return null;
            }

            if (!$prevHistory->request->has('message')) {
                return null;
            }

            $data = json_encode($prevHistory->request->toArray(), JSON_THROW_ON_ERROR);
            /** @var ?TelegramUpdate $prevUpdate */
            $prevUpdate = $this->serializer->deserialize($data, TelegramUpdate::class, 'json');
            if ($prevUpdate->getMessageObj()->isEmpty()) {
                return null;
            }
            if ($prevUpdate->getFrom()->username === $this->configPolicy->botName) {
                return null;
            }

            return $prevUpdate->getMessageObj();
        } catch (Throwable $throwable) {
            return null;
        }
    }

    private function findOrCreateUserBan(
        TelegramChatEntity $chat,
        TelegramChatUserEntity $user,
        TelegramMessage $spammerMessage,
        TelegramMessage $banMessage
    ): TelegramChatUserBanEntity {
        $userBan = $this->entityManager
            ->getRepository(TelegramChatUserBanEntity::class)
            ->findOneBy([
                'chatId' => $chat->chatId,
                'reporterId' => $user->userId,
                'banMessageId' => $banMessage->message_id,
            ]);

        if (null === $userBan) {
            $userBan = new TelegramChatUserBanEntity();
            $userBan->chatId = $chat->chatId;
            $userBan->reporterId = $user->userId;
            $userBan->banMessageId = $banMessage->message_id;
            $userBan->spammerId = $spammerMessage->from->id;
            $userBan->spamMessageId = $spammerMessage->message_id;

            $this->entityManager->persist($userBan);
            $this->entityManager->flush();
        }

        return $userBan;
    }

    protected function findOrCreateUserBanVote(
        TelegramChatEntity $chat,
        TelegramChatUserEntity $user,
        TelegramChatUserBanEntity $userBan
    ): ?TelegramChatUserBanVoteEntity {
        $userBanVote = $this->entityManager
            ->getRepository(TelegramChatUserBanVoteEntity::class)
            ->findOneBy([
                'user' => $user,
                'ban' => $userBan,
                'chatId' => $chat->chatId,
            ]);

        if (null === $userBanVote) {
            $userBanVote = new TelegramChatUserBanVoteEntity();
            $userBanVote->user = $user;
            $userBanVote->ban = $userBan;
            $userBanVote->chatId = $chat->chatId;
        }

        $userBanVote->vote = $this->update->callback_query->data ?? TelegramChatUserBanVoteEntity::TYPE_DO_BAN;
        $this->entityManager->persist($userBanVote);
        $this->entityManager->flush();

        return $userBanVote;
    }
}
