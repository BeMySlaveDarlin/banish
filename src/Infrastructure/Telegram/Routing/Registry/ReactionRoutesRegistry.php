<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\Reaction\ReactionRemovedCommand;
use App\Application\Command\Telegram\Reaction\StartBanByReactionCommand;
use App\Application\Command\Telegram\Reaction\VoteByReactionCommand;
use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Service\ChatConfigServiceInterface;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class ReactionRoutesRegistry implements RouteRegistryInterface
{
    public function __construct(
        private BanRepository $banRepository,
        private ChatRepository $chatRepository,
        private ChatConfigServiceInterface $configService
    ) {
    }

    public function matches(TelegramUpdate $update, string $botName): bool
    {
        if ($update->message_reaction === null) {
            return false;
        }

        $chatId = $update->message_reaction->chat->id ?? 0;
        $chat = $this->chatRepository->findByChatId($chatId);
        if ($chat === null) {
            return true;
        }

        return $this->configService->isReactionsEnabled($chat);
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        if ($update->message_reaction === null) {
            return UnsupportedCommand::class;
        }

        $chatId = $update->message_reaction->chat->id ?? 0;
        $ban = $this->banRepository->findBySpamMessage(
            $chatId,
            $update->message_reaction->message_id
        );

        if ($ban === null) {
            return StartBanByReactionCommand::class;
        }

        $emoji = $update->message_reaction->getNewEmoji();
        if ($emoji === null) {
            return ReactionRemovedCommand::class;
        }

        return VoteByReactionCommand::class;
    }
}
