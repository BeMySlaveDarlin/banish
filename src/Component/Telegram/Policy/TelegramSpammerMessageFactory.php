<?php

declare(strict_types=1);

namespace App\Component\Telegram\Policy;

use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\Entity\TelegramRequestHistoryEntity;
use App\Component\Telegram\ValueObject\TelegramMessage;
use App\Component\Telegram\ValueObject\TelegramUpdate;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TelegramSpammerMessageFactory
{
    public function __construct(
        protected LoggerInterface $logger,
        protected EntityManagerInterface $entityManager,
        protected SerializerInterface $serializer,
        protected TelegramConfigPolicy $configPolicy,
        protected TelegramApiClientPolicy $apiClientPolicy,
        protected TelegramUpdate $update
    ) {
    }

    public function isReply(): bool
    {
        return $this->update->getMessageObj()->hasReply();
    }

    public function getReplyMessage(): ?TelegramMessage
    {
        return $this->update->getMessageObj()->reply_to_message;
    }

    public function isUserMention(): bool
    {
        return $this->update->getMessageObj()->hasUserMention($this->configPolicy->botName);
    }

    public function getUserMentionedMessage(): ?TelegramMessage
    {
        $username = $this->update->getMessageObj()->getUserMention($this->configPolicy->botName);

        $user = $this->entityManager
            ->getRepository(TelegramChatUserEntity::class)
            ->findUserByName(
                $this->update->getChat()->id,
                $username,
            );

        if (!$user) {
            return null;
        }

        $message = [
            "message_id" => null,
            "from" => [
                "id" => $user->userId,
                "username" => $username,
            ],
            "chat" => $this->update->getChat(),
            "date" => time(),
        ];

        return $this->serializer->deserialize(
            json_encode($message, JSON_THROW_ON_ERROR),
            TelegramMessage::class,
            'json'
        );
    }

    public function getPrevMessage(): ?TelegramMessage
    {
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
    }
}
