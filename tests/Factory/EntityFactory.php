<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Common\ValueObject\JsonBValue;
use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanVoteEntity;
use App\Domain\Telegram\Entity\TelegramChatUserEntity;
use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Enum\UserStatus;
use App\Domain\Telegram\Enum\VoteType;
use DateTimeImmutable;
use ReflectionProperty;

final class EntityFactory
{
    /**
     * @param array<string, mixed> $options
     */
    public static function createChat(int $chatId, array $options = []): TelegramChatEntity
    {
        $chat = new TelegramChatEntity();
        $chat->chatId = $chatId;
        $chat->type = isset($options['type']) && is_string($options['type']) ? $options['type'] : 'supergroup';
        $chat->name = isset($options['name']) && is_string($options['name']) ? $options['name'] : 'Test Chat';
        $chat->isEnabled = isset($options['isEnabled']) && is_bool($options['isEnabled']) ? $options['isEnabled'] : true;
        /** @var array<string, mixed> $optionsData */
        $optionsData = isset($options['options']) && is_array($options['options']) ? $options['options'] : TelegramChatEntity::getDefaultOptions();
        $chat->options = new JsonBValue($optionsData);
        $chat->createdAt = new DateTimeImmutable();
        $chat->updatedAt = new DateTimeImmutable();

        if (isset($options['id'])) {
            /** @var int|string $rawId */
            $rawId = $options['id'];
            self::setProperty($chat, 'id', (string) $rawId);
        }

        return $chat;
    }

    public static function createUser(
        int $chatId,
        int $userId,
        bool $isAdmin = false,
        bool $isTrusted = false,
    ): TelegramChatUserEntity {
        $user = new TelegramChatUserEntity();
        $user->chatId = $chatId;
        $user->userId = $userId;
        $user->username = 'user_' . $userId;
        $user->name = 'User ' . $userId;
        $user->isAdmin = $isAdmin;
        $user->isBot = false;
        $user->status = $isTrusted ? UserStatus::ACTIVE : UserStatus::ACTIVE;
        $user->createdAt = new DateTimeImmutable();
        $user->updatedAt = new DateTimeImmutable();

        return $user;
    }

    public static function createBan(
        int $chatId,
        int $spammerId,
        int $reporterId,
        BanStatus $status = BanStatus::PENDING,
    ): TelegramChatUserBanEntity {
        $ban = TelegramChatUserBanEntity::create(
            chatId: $chatId,
            reporterId: $reporterId,
            spammerId: $spammerId,
            banMessageId: random_int(1, 999999),
        );

        if ($status !== BanStatus::PENDING) {
            self::forceBanStatus($ban, $status);
        }

        return $ban;
    }

    public static function createVote(
        TelegramChatUserBanEntity $ban,
        TelegramChatUserEntity $user,
        VoteType $type,
    ): TelegramChatUserBanVoteEntity {
        $vote = new TelegramChatUserBanVoteEntity();
        $vote->banId = $ban->id ?? '0';
        $vote->chatId = $ban->chatId;
        $vote->userId = $user->userId;
        $vote->vote = $type;
        $vote->ban = $ban;
        $vote->user = $user;
        $vote->createdAt = new DateTimeImmutable();
        $vote->updatedAt = new DateTimeImmutable();

        return $vote;
    }

    private static function forceBanStatus(TelegramChatUserBanEntity $ban, BanStatus $status): void
    {
        $ref = new ReflectionProperty(TelegramChatUserBanEntity::class, 'status');
        $ref->setValue($ban, $status);
    }

    private static function setProperty(object $object, string $property, mixed $value): void
    {
        $ref = new ReflectionProperty($object::class, $property);
        $ref->setValue($object, $value);
    }
}
