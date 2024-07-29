<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatEntity;
use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\Entity\TelegramRequestHistoryEntity;
use App\Component\Telegram\Policy\TelegramApiClientPolicy;
use App\Component\Telegram\Policy\TelegramConfigPolicy;
use App\Component\Telegram\ValueObject\TelegramUpdate;
use App\Service\UseCase\NonTransactionalUseCaseInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract readonly class AbstractTelegramUseCase implements NonTransactionalUseCaseInterface
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected SerializerInterface $serializer,
        protected TelegramConfigPolicy $configPolicy,
        protected TelegramApiClientPolicy $apiClientPolicy,
        protected TelegramUpdate $update
    ) {
    }

    abstract public function handleUpdate(TelegramChatEntity $chat, TelegramChatUserEntity $user): string;

    public function execute(): string
    {
        $requestHistory = $this->getRequestHistory();
        if (!$requestHistory->isNew) {
            return $requestHistory->response->get('message');
        }

        $chat = $this->persistChat();
        $user = $this->persistUser();

        $response = $this->handleUpdate($chat, $user);
        $requestHistory->setResponse(['message' => $response]);
        $this->entityManager->persist($requestHistory);
        $this->entityManager->flush();

        return $response;
    }

    private function persistChat(): TelegramChatEntity
    {
        $chat = $this->entityManager
            ->getRepository(TelegramChatEntity::class)
            ->findOneBy([
                'chatId' => $this->update->message->chat->id,
            ]);

        if (null === $chat) {
            $chat = new TelegramChatEntity();
            $chat->chatId = (string) $this->update->message->chat->id;
            $chat->type = $this->update->message->chat->type;
            $chat->isEnabled = false;
        }

        if (empty($chat->name)) {
            $chat->name = $this->update->message->chat->getAlias();
        }

        $this->entityManager->persist($chat);
        $this->entityManager->flush();

        return $chat;
    }

    private function persistUser(): TelegramChatUserEntity
    {
        $user = $this->entityManager
            ->getRepository(TelegramChatUserEntity::class)
            ->findOneBy([
                'chatId' => $this->update->message->chat->id,
                'userId' => $this->update->message->from->id,
            ]);

        if (null === $user) {
            $user = new TelegramChatUserEntity();
            $user->chatId = (string) $this->update->message->chat->id;
            $user->userId = (string) $this->update->message->from->id;
        }

        if (empty($user->name)) {
            $firstName = $this->update->message->from->first_name;
            $lastName = $this->update->message->from->last_name;
            $name = "$firstName $lastName";
            $user->name = trim($name);
        }
        if (empty($user->username)) {
            $user->username = $this->update->message->from->username;
        }

        $chatMember = $this->apiClientPolicy->getChatMember(
            (string) $this->update->message->chat->id,
            (string) $this->update->message->from->id
        );

        $user->isBot = $this->update->message->from->is_bot;
        $user->isAdmin = $chatMember && $chatMember->isAdmin();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function getRequestHistory(): TelegramRequestHistoryEntity
    {
        $requestHistory = $this->entityManager
            ->getRepository(TelegramRequestHistoryEntity::class)
            ->findOneBy([
                'chatId' => $this->update->message->chat->id,
                'fromId' => $this->update->message->from->id,
                'messageId' => $this->update->message->message_id,
                'updateId' => $this->update->update_id,
            ]);

        if (null === $requestHistory) {
            $requestHistory = new TelegramRequestHistoryEntity();
            $requestHistory->chatId = (string) $this->update->message->chat->id;
            $requestHistory->fromId = (string) $this->update->message->from->id;
            $requestHistory->messageId = (string) $this->update->message->message_id;
            $requestHistory->updateId = (string) $this->update->update_id;
            $requestHistory->setRequest($this->update->request->toArray());
            $requestHistory->isNew = true;
        }

        return $requestHistory;
    }
}
