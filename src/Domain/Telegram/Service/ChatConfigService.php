<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Constants\ChatDefaults;
use App\Domain\Telegram\Constants\Emoji;
use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Infrastructure\Doctrine\Type\JsonBValue;

class ChatConfigService implements ChatConfigServiceInterface
{
    public function __construct(
        private readonly ChatRepository $chatRepository
    ) {
    }

    public function flush(): void
    {
        $this->chatRepository->flush();
    }

    public function getMinMessagesForTrust(TelegramChatEntity $chat): int
    {
        if ($chat->options === null) {
            return ChatDefaults::DEFAULT_MIN_MESSAGES_FOR_TRUST;
        }
        $value = $chat->options->get(
            ChatDefaults::OPTION_MIN_MESSAGES_FOR_TRUST,
            ChatDefaults::DEFAULT_MIN_MESSAGES_FOR_TRUST
        );
        return is_int($value) ? $value : ChatDefaults::DEFAULT_MIN_MESSAGES_FOR_TRUST;
    }

    public function setMinMessagesForTrust(TelegramChatEntity $chat, int $value): void
    {
        $options = $chat->options?->toArray() ?? [];
        $options[ChatDefaults::OPTION_MIN_MESSAGES_FOR_TRUST] = $value;
        $chat->options = new JsonBValue($options);
        $this->chatRepository->save($chat, false);
    }

    public function getVotesRequired(TelegramChatEntity $chat): int
    {
        if ($chat->options === null) {
            return ChatDefaults::DEFAULT_VOTES_REQUIRED;
        }
        $value = $chat->options->get(
            ChatDefaults::OPTION_BAN_VOTES_REQUIRED,
            ChatDefaults::DEFAULT_VOTES_REQUIRED
        );
        return is_int($value) ? $value : ChatDefaults::DEFAULT_VOTES_REQUIRED;
    }

    public function setVotesRequired(TelegramChatEntity $chat, int $value): void
    {
        $options = $chat->options?->toArray() ?? [];
        $options[ChatDefaults::OPTION_BAN_VOTES_REQUIRED] = $value;
        $chat->options = new JsonBValue($options);
        $this->chatRepository->save($chat, false);
    }

    public function isDeleteMessagesEnabled(TelegramChatEntity $chat): bool
    {
        return (bool) $chat->options?->get(
            ChatDefaults::OPTION_DELETE_MESSAGE,
            ChatDefaults::DEFAULT_DELETE_MESSAGES
        );
    }

    public function setDeleteMessagesEnabled(TelegramChatEntity $chat, bool $enabled): void
    {
        $options = $chat->options?->toArray() ?? [];
        $options[ChatDefaults::OPTION_DELETE_MESSAGE] = $enabled;
        $chat->options = new JsonBValue($options);
        $this->chatRepository->save($chat, false);
    }

    public function isReactionsEnabled(TelegramChatEntity $chat): bool
    {
        return (bool) $chat->options?->get(
            ChatDefaults::OPTION_ENABLE_REACTIONS,
            ChatDefaults::DEFAULT_ENABLE_REACTIONS
        );
    }

    public function setReactionsEnabled(TelegramChatEntity $chat, bool $enabled): void
    {
        $options = $chat->options?->toArray() ?? [];
        $options[ChatDefaults::OPTION_ENABLE_REACTIONS] = $enabled;
        $chat->options = new JsonBValue($options);
        $this->chatRepository->save($chat, false);
    }

    public function isDeleteOnlyEnabled(TelegramChatEntity $chat): bool
    {
        return (bool) $chat->options?->get(
            ChatDefaults::OPTION_DELETE_ONLY,
            ChatDefaults::DEFAULT_DELETE_ONLY
        );
    }

    public function setDeleteOnlyEnabled(TelegramChatEntity $chat, bool $enabled): void
    {
        $options = $chat->options?->toArray() ?? [];
        $options[ChatDefaults::OPTION_DELETE_ONLY] = $enabled;
        $chat->options = new JsonBValue($options);
        $this->chatRepository->save($chat, false);
    }

    public function getBanEmoji(TelegramChatEntity $chat): string
    {
        $emoji = $chat->options?->get(
            ChatDefaults::OPTION_BAN_EMOJI,
            Emoji::DEFAULT_BAN
        );
        return is_string($emoji) ? $emoji : Emoji::DEFAULT_BAN;
    }

    public function setBanEmoji(TelegramChatEntity $chat, string $emoji): void
    {
        $options = $chat->options?->toArray() ?? [];
        $options[ChatDefaults::OPTION_BAN_EMOJI] = $emoji;
        $chat->options = new JsonBValue($options);
        $this->chatRepository->save($chat, false);
    }

    public function getForgiveEmoji(TelegramChatEntity $chat): string
    {
        $emoji = $chat->options?->get(
            ChatDefaults::OPTION_FORGIVE_EMOJI,
            Emoji::DEFAULT_FORGIVE
        );
        return is_string($emoji) ? $emoji : Emoji::DEFAULT_FORGIVE;
    }

    public function setForgiveEmoji(TelegramChatEntity $chat, string $emoji): void
    {
        $options = $chat->options?->toArray() ?? [];
        $options[ChatDefaults::OPTION_FORGIVE_EMOJI] = $emoji;
        $chat->options = new JsonBValue($options);
        $this->chatRepository->save($chat, false);
    }
}
