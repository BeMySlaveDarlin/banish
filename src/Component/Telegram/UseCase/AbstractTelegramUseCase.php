<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

use App\Component\Telegram\Entity\TelegramChatEntity;
use App\Component\Telegram\Entity\TelegramChatUserEntity;
use App\Component\Telegram\Entity\TelegramRequestHistoryEntity;
use App\Component\Telegram\Policy\TelegramApiClientPolicy;
use App\Component\Telegram\Policy\TelegramConfigPolicy;
use App\Component\Telegram\Policy\TelegramSpammerMessageFactory;
use App\Component\Telegram\ValueObject\TelegramUpdate;
use App\Service\UseCase\NonTransactionalUseCaseInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract readonly class AbstractTelegramUseCase implements NonTransactionalUseCaseInterface
{
    protected TelegramSpammerMessageFactory $spammerMessageFactory;

    public function __construct(
        protected LoggerInterface $logger,
        protected EntityManagerInterface $entityManager,
        protected SerializerInterface $serializer,
        protected TelegramConfigPolicy $configPolicy,
        protected TelegramApiClientPolicy $apiClientPolicy,
        protected TelegramUpdate $update
    ) {
        $this->spammerMessageFactory = new TelegramSpammerMessageFactory(
            $logger,
            $entityManager,
            $serializer,
            $configPolicy,
            $apiClientPolicy,
            $update
        );
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
                'chatId' => $this->update->getChat()->id,
            ]);

        if (null === $chat) {
            $chat = new TelegramChatEntity();
            $chat->chatId = $this->update->getChat()->id;
            $chat->type = $this->update->getChat()->type;
            $chat->isEnabled = false;
        }

        if (empty($chat->name)) {
            $chat->name = $this->update->getChat()->getAlias();
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
                'chatId' => $this->update->getChat()->id,
                'userId' => $this->update->getFrom()->id,
            ]);

        if (null === $user) {
            $user = new TelegramChatUserEntity();
            $user->chatId = $this->update->getChat()->id;
            $user->userId = $this->update->getFrom()->id;
        }

        if (empty($user->name)) {
            $firstName = $this->update->getFrom()->first_name;
            $lastName = $this->update->getFrom()->last_name;
            $name = "$firstName $lastName";
            $user->name = trim($name);
        }
        if (empty($user->username)) {
            $user->username = $this->update->getFrom()->username;
        }

        $chatMember = $this->apiClientPolicy->getChatMember(
            $this->update->getChat()->id,
            $this->update->getFrom()->id
        );

        $user->isBot = $this->update->getFrom()->is_bot;
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
                'chatId' => $this->update->getChat()->id,
                'fromId' => $this->update->getFrom()->id,
                'messageId' => $this->update->getMessageObj()->message_id,
                'updateId' => $this->update->update_id,
            ]);

        if (null === $requestHistory) {
            $requestHistory = new TelegramRequestHistoryEntity();
            $requestHistory->chatId = $this->update->getChat()->id;
            $requestHistory->fromId = $this->update->getFrom()->id;
            $requestHistory->messageId = $this->update->getMessageObj()->message_id;
            $requestHistory->updateId = $this->update->update_id;
            $requestHistory->setRequest($this->update->request->toArray());
            $requestHistory->isNew = true;
        }

        return $requestHistory;
    }
}
