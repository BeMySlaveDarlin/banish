<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Telegram\ValueObject\TelegramCallbackQuery;
use App\Domain\Telegram\ValueObject\TelegramDeletedMessage;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageEntity;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;
use App\Domain\Telegram\ValueObject\TelegramMessageReaction;
use App\Domain\Telegram\ValueObject\TelegramMyChatMember;
use App\Domain\Telegram\ValueObject\TelegramReactionType;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final class TelegramUpdateFactory
{
    private static int $updateIdCounter = 1;

    public static function createTextMessage(int $chatId, int $userId, string $text): TelegramUpdate
    {
        $update = new TelegramUpdate();
        $update->update_id = self::nextUpdateId();

        $message = new TelegramMessage();
        $message->message_id = random_int(1, 999999);
        $message->date = time();
        $message->text = $text;
        $message->chat = self::createChat($chatId);
        $message->from = self::createFrom($userId);
        $message->sticker = null;
        $message->document = null;

        $update->message = $message;

        return $update;
    }

    public static function createBanCommand(
        int $chatId,
        int $userId,
        int $replyToMessageId,
        int $replyFromUserId,
    ): TelegramUpdate {
        $update = new TelegramUpdate();
        $update->update_id = self::nextUpdateId();

        $entity = new TelegramMessageEntity();
        $entity->type = 'bot_command';
        $entity->offset = 0;
        $entity->length = 4;

        $replyMessage = new TelegramMessage();
        $replyMessage->message_id = $replyToMessageId;
        $replyMessage->date = time();
        $replyMessage->chat = self::createChat($chatId);
        $replyMessage->from = self::createFrom($replyFromUserId);
        $replyMessage->sticker = null;
        $replyMessage->document = null;

        $message = new TelegramMessage();
        $message->message_id = random_int(1, 999999);
        $message->date = time();
        $message->text = '/ban';
        $message->chat = self::createChat($chatId);
        $message->from = self::createFrom($userId);
        $message->entities = [$entity];
        $message->reply_to_message = $replyMessage;
        $message->sticker = null;
        $message->document = null;

        $update->message = $message;

        return $update;
    }

    public static function createCallbackQuery(
        int $chatId,
        int $userId,
        string $data,
        int $messageId,
    ): TelegramUpdate {
        $update = new TelegramUpdate();
        $update->update_id = self::nextUpdateId();

        $message = new TelegramMessage();
        $message->message_id = $messageId;
        $message->date = time();
        $message->chat = self::createChat($chatId);
        $message->from = self::createFrom(0);
        $message->sticker = null;
        $message->document = null;

        $callback = new TelegramCallbackQuery();
        $callback->id = (string) random_int(1, 999999);
        $callback->from = self::createFrom($userId);
        $callback->message = $message;
        $callback->data = $data;
        $callback->chat_instance = (string) $chatId;

        $update->callback_query = $callback;

        return $update;
    }

    public static function createReaction(
        int $chatId,
        int $userId,
        int $messageId,
        string $emoji,
    ): TelegramUpdate {
        $update = new TelegramUpdate();
        $update->update_id = self::nextUpdateId();

        $reactionType = new TelegramReactionType();
        $reactionType->type = 'emoji';
        $reactionType->emoji = $emoji;

        $reaction = new TelegramMessageReaction();
        $reaction->chat = self::createChat($chatId);
        $reaction->user = self::createFrom($userId);
        $reaction->message_id = $messageId;
        $reaction->date = time();
        $reaction->new_reaction = [$reactionType];
        $reaction->old_reaction = [];

        $update->message_reaction = $reaction;

        return $update;
    }

    public static function createMyChatMember(
        int $chatId,
        string $oldStatus,
        string $newStatus,
    ): TelegramUpdate {
        $update = new TelegramUpdate();
        $update->update_id = self::nextUpdateId();

        $oldMember = new TelegramMessageFrom();
        $oldMember->id = 0;
        $oldMember->is_bot = true;

        $newMember = new TelegramMessageFrom();
        $newMember->id = 0;
        $newMember->is_bot = true;

        $myChatMember = new TelegramMyChatMember();
        $myChatMember->chat = self::createChat($chatId);
        $myChatMember->from = self::createFrom(0);
        $myChatMember->date = time();
        $myChatMember->old_chat_member = $oldMember;
        $myChatMember->new_chat_member = $newMember;

        $update->my_chat_member = $myChatMember;

        return $update;
    }

    public static function createDeletedMessage(int $chatId, int $messageId): TelegramUpdate
    {
        $update = new TelegramUpdate();
        $update->update_id = self::nextUpdateId();

        $deleted = new TelegramDeletedMessage();
        $deleted->chat = self::createChat($chatId);
        $deleted->message_id = $messageId;
        $deleted->date = time();

        $update->message_deleted_by_user = $deleted;

        return $update;
    }

    public static function resetCounter(): void
    {
        self::$updateIdCounter = 1;
    }

    private static function createChat(int $chatId): TelegramMessageChat
    {
        $chat = new TelegramMessageChat();
        $chat->id = $chatId;
        $chat->type = 'supergroup';
        $chat->title = 'Test Chat';

        return $chat;
    }

    private static function createFrom(int $userId): TelegramMessageFrom
    {
        $from = new TelegramMessageFrom();
        $from->id = $userId;
        $from->username = 'user_' . $userId;
        $from->first_name = 'User';
        $from->last_name = (string) $userId;
        $from->language_code = 'en';
        $from->is_bot = false;

        return $from;
    }

    private static function nextUpdateId(): int
    {
        return self::$updateIdCounter++;
    }
}
