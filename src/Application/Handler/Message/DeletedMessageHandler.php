<?php

declare(strict_types=1);

namespace App\Application\Handler\Message;

use App\Application\Command\Telegram\Message\DeletedMessageCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use App\Domain\Telegram\Constants\Messages;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Infrastructure\Telegram\Attribute\AsTelegramHandler;
use Psr\Log\LoggerInterface;

#[AsTelegramHandler(DeletedMessageCommand::class)]
final readonly class DeletedMessageHandler implements TelegramHandlerInterface
{
    public function __construct(
        private RequestHistoryRepository $requestHistoryRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param DeletedMessageCommand $command
     *
     * @return string
     */
    public function handle(TelegramCommandInterface $command): string
    {
        $chatId = $command->getChatId();
        $messageId = $command->getMessageId();

        if ($chatId === 0 || $messageId === 0) {
            return Messages::MESSAGE_SILENT_OK;
        }

        try {
            $this->requestHistoryRepository->markMessageDeleted($chatId, $messageId);
            $this->logger->info("Message $messageId in chat $chatId marked as deleted");
        } catch (\Exception $e) {
            $this->logger->error("Failed to mark message $messageId as deleted: {$e->getMessage()}");
        }

        return Messages::MESSAGE_SILENT_OK;
    }
}
