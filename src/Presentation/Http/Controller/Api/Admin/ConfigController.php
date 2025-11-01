<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api\Admin;

use App\Domain\Admin\Entity\AdminSessionEntity;
use App\Domain\Admin\Enum\AdminActionType;
use App\Domain\Admin\Service\AdminActionLogService;
use App\Domain\Admin\Service\AdminSessionService;
use App\Domain\Telegram\Constants\ChatDefaults;
use App\Domain\Telegram\Constants\Emoji;
use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ConfigController extends AbstractAdminController
{
    public function __construct(
        protected ChatRepository $chatRepository,
        protected ChatConfigServiceInterface $configService,
        protected UserRepository $userRepository,
        protected AdminActionLogService $logService,
        AdminSessionService $sessionService,
    ) {
        parent::__construct($sessionService);
    }

    public function getAction(
        int $chatId,
        Request $request
    ): JsonResponse {
        $chat = $this->getChatWithAccess($chatId, $request);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found or access denied'], 403);
        }

        $response = $this->json([
            'chatId' => $chat->chatId,
            'title' => $chat->name,
            'config' => [
                'banEmoji' => $this->configService->getBanEmoji($chat),
                'forgiveEmoji' => $this->configService->getForgiveEmoji($chat),
                'votesRequired' => $this->configService->getVotesRequired($chat),
                'minMessagesForTrust' => $this->configService->getMinMessagesForTrust($chat),
                'enabled' => $chat->isEnabled,
                'deleteMessages' => $chat->getOption(ChatDefaults::OPTION_DELETE_MESSAGE) ?? ChatDefaults::DEFAULT_DELETE_MESSAGES,
                'deleteOnly' => $chat->getOption(ChatDefaults::OPTION_DELETE_ONLY) ?? ChatDefaults::DEFAULT_DELETE_ONLY,
                'enableReactions' => $chat->getOption(ChatDefaults::OPTION_ENABLE_REACTIONS) ?? ChatDefaults::DEFAULT_ENABLE_REACTIONS,
            ],
        ]);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }

    public function updateAction(
        int $chatId,
        Request $request,
        #[CurrentUser]
        AdminSessionEntity $session,
    ): JsonResponse {
        $chat = $this->getChatWithAccess($chatId, $request);
        if (!$chat) {
            return $this->json(['error' => 'Chat not found or access denied'], 403);
        }

        /** @var array<string, mixed> $data */
        $data = \json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];
        $changedFields = [];

        $this->ensureAllDefaultOptionsExist($chat);

        if (isset($data['banEmoji']) && is_string($data['banEmoji'])) {
            $this->configService->setBanEmoji($chat, $data['banEmoji']);
            $changedFields['banEmoji'] = $data['banEmoji'];
        }

        if (isset($data['forgiveEmoji']) && is_string($data['forgiveEmoji'])) {
            $this->configService->setForgiveEmoji($chat, $data['forgiveEmoji']);
            $changedFields['forgiveEmoji'] = $data['forgiveEmoji'];
        }

        if (isset($data['votesRequired']) && is_int($data['votesRequired'])) {
            $this->configService->setVotesRequired($chat, $data['votesRequired']);
            $changedFields['votesRequired'] = $data['votesRequired'];
        }

        if (isset($data['minMessagesForTrust']) && is_int($data['minMessagesForTrust'])) {
            $this->configService->setMinMessagesForTrust($chat, $data['minMessagesForTrust']);
            $changedFields['minMessagesForTrust'] = $data['minMessagesForTrust'];
        }

        if (isset($data['enabled'])) {
            $chat->isEnabled = (bool) $data['enabled'];
            $changedFields['enabled'] = (bool) $data['enabled'];
        }

        if (isset($data['deleteMessages'])) {
            $this->configService->setDeleteMessagesEnabled($chat, (bool) $data['deleteMessages']);
            $changedFields['deleteMessages'] = (bool) $data['deleteMessages'];
        }

        if (isset($data['enableReactions'])) {
            $this->configService->setReactionsEnabled($chat, (bool) $data['enableReactions']);
            $changedFields['enableReactions'] = (bool) $data['enableReactions'];
        }

        if (isset($data['deleteOnly'])) {
            $this->configService->setDeleteOnlyEnabled($chat, (bool) $data['deleteOnly']);
            $changedFields['deleteOnly'] = (bool) $data['deleteOnly'];
        }

        $this->chatRepository->save($chat);

        if (!empty($changedFields)) {
            $this->logService->log(
                $session->userId,
                $chatId,
                AdminActionType::CONFIG_UPDATE,
                $changedFields,
                'Updated chat configuration'
            );
        }

        $response = $this->json(['success' => true, 'message' => 'Config updated']);

        $this->refreshSessionCookie($request, $response);

        return $response;
    }

    private function ensureAllDefaultOptionsExist(TelegramChatEntity $chat): void
    {
        $defaultOptions = [
            ChatDefaults::OPTION_BAN_VOTES_REQUIRED => ChatDefaults::DEFAULT_VOTES_REQUIRED,
            ChatDefaults::OPTION_DELETE_MESSAGE => ChatDefaults::DEFAULT_DELETE_MESSAGES,
            ChatDefaults::OPTION_DELETE_ONLY => ChatDefaults::DEFAULT_DELETE_ONLY,
            ChatDefaults::OPTION_MIN_MESSAGES_FOR_TRUST => ChatDefaults::DEFAULT_MIN_MESSAGES_FOR_TRUST,
            ChatDefaults::OPTION_BAN_EMOJI => Emoji::DEFAULT_BAN,
            ChatDefaults::OPTION_FORGIVE_EMOJI => Emoji::DEFAULT_FORGIVE,
            ChatDefaults::OPTION_ENABLE_REACTIONS => ChatDefaults::DEFAULT_ENABLE_REACTIONS,
        ];

        $needsSave = false;
        foreach ($defaultOptions as $option => $defaultValue) {
            try {
                $chat->getOption($option);
            } catch (\Throwable) {
                $chat->setOption($option, $defaultValue);
                $needsSave = true;
            }
        }

        if ($needsSave) {
            $this->chatRepository->save($chat);
        }
    }
}
