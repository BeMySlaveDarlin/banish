<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Infrastructure\Doctrine\Type\JsonBValue;

class ChatConfigService
{
    public function __construct(
        private ChatRepository $chatRepository
    ) {
    }

    public function getMinMessagesForTrust(TelegramChatEntity $chat): int
    {
        return (int) $chat->options->get(
            TelegramChatEntity::OPTION_MIN_MESSAGES_FOR_TRUST,
            TelegramChatEntity::DEFAULT_MIN_MESSAGES_FOR_TRUST
        );
    }

    public function setMinMessagesForTrust(TelegramChatEntity $chat, int $value): void
    {
        $options = $chat->options->toArray();
        $options[TelegramChatEntity::OPTION_MIN_MESSAGES_FOR_TRUST] = $value;
        $chat->options = new JsonBValue($options);

        $this->chatRepository->save($chat);
    }

    public function getVotesRequired(TelegramChatEntity $chat): int
    {
        return (int) $chat->options->get(
            TelegramChatEntity::OPTION_BAN_VOTES_REQUIRED,
            TelegramChatEntity::DEFAULT_VOTES_REQUIRED
        );
    }

    public function setVotesRequired(TelegramChatEntity $chat, int $value): void
    {
        $options = $chat->options->toArray();
        $options[TelegramChatEntity::OPTION_BAN_VOTES_REQUIRED] = $value;
        $chat->options = new JsonBValue($options);

        $this->chatRepository->save($chat);
    }

    public function isDeleteMessagesEnabled(TelegramChatEntity $chat): bool
    {
        return (bool) $chat->options->get(
            TelegramChatEntity::OPTION_DELETE_MESSAGE,
            TelegramChatEntity::DEFAULT_DELETE_MESSAGES
        );
    }

    public function setDeleteMessagesEnabled(TelegramChatEntity $chat, bool $enabled): void
    {
        $options = $chat->options->toArray();
        $options[TelegramChatEntity::OPTION_DELETE_MESSAGE] = $enabled;
        $chat->options = new JsonBValue($options);
        $this->chatRepository->save($chat);
    }
}
