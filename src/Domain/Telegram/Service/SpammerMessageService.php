<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SpammerMessageService
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private RequestHistoryRepository $requestHistoryRepository,
        private SerializerInterface $serializer,
        private string $botName
    ) {
    }

    public function getSpammerMessage(TelegramUpdate $update): ?TelegramMessage
    {
        if ($this->isReply($update)) {
            return $this->getReplyMessage($update);
        }

        if ($this->isUserMention($update)) {
            return $this->getUserMentionedMessage($update);
        }

        return $this->getPreviousMessage($update);
    }

    private function isReply(TelegramUpdate $update): bool
    {
        return $update->getMessageObj()->hasReply();
    }

    private function getReplyMessage(TelegramUpdate $update): ?TelegramMessage
    {
        return $update->getMessageObj()->reply_to_message;
    }

    private function isUserMention(TelegramUpdate $update): bool
    {
        return $update->getMessageObj()->hasUserMention($this->botName);
    }

    private function getUserMentionedMessage(TelegramUpdate $update): ?TelegramMessage
    {
        $username = $update->getMessageObj()->getUserMention($this->botName);

        $user = $this->userRepository->findByChatAndUsername(
            $update->getChat()->id,
            $username
        );

        if (!$user) {
            return null;
        }

        $messageData = [
            'message_id' => null,
            'from' => [
                'id' => $user->userId,
                'is_bot' => $user->isBot,
                'first_name' => $user->name,
                'username' => $user->username,
            ],
            'chat' => [
                'id' => $update->getChat()->id,
                'type' => $update->getChat()->type,
            ],
            'date' => time(),
            'text' => '',
        ];

        return $this->serializer->deserialize(json_encode($messageData), TelegramMessage::class, 'json');
    }

    private function getPreviousMessage(TelegramUpdate $update): ?TelegramMessage
    {
        $history = $this->requestHistoryRepository->findPreviousMessage(
            $update->getChat()->id,
            $update->getFrom()->id,
            $update->getMessageObj()->message_id
        );

        if (!$history) {
            return null;
        }

        $requestData = $history->request->toArray();
        $messageData = $requestData['message'] ?? null;

        if (!$messageData) {
            return null;
        }

        return $this->serializer->deserialize(json_encode($messageData), TelegramMessage::class, 'json');
    }
}
