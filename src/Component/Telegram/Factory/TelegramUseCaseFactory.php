<?php

declare(strict_types=1);

namespace App\Component\Telegram\Factory;

use App\Component\Telegram\Policy\TelegramApiClientPolicy;
use App\Component\Telegram\Policy\TelegramConfigPolicy;
use App\Component\Telegram\UseCase\TelegramBanStartProcedureUseCase;
use App\Component\Telegram\UseCase\TelegramBanVoteStartProcedureUseCase;
use App\Component\Telegram\UseCase\TelegramCommandHelpUseCase;
use App\Component\Telegram\UseCase\TelegramCommandUseCaseInterface;
use App\Component\Telegram\UseCase\TelegramUnsupportedUseCase;
use App\Component\Telegram\ValueObject\TelegramUpdate;
use App\Service\UseCase\UseCaseInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

readonly class TelegramUseCaseFactory
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private TelegramConfigPolicy $configPolicy,
        private TelegramApiClientPolicy $apiClientPolicy
    ) {
    }

    public function getUseCase(TelegramUpdate $update): ?UseCaseInterface
    {
        $arguments = [
            'logger' => $this->logger,
            'entityManager' => $this->entityManager,
            'serializer' => $this->serializer,
            'configPolicy' => $this->configPolicy,
            'apiClientPolicy' => $this->apiClientPolicy,
            'update' => $update,
        ];
        if ($update->getFrom()->is_bot) {
            return new TelegramUnsupportedUseCase(...$arguments);
        }

        if ($update->getMessageObj()->isEmpty()) {
            return new TelegramUnsupportedUseCase(...$arguments);
        }

        if ($update->getChat()->isPrivate()) {
            return new TelegramCommandHelpUseCase(...$arguments);
        }

        if ($update->getMessageObj()->isBotCommand()) {
            $command = $update->getMessageObj()->getCommand($this->configPolicy->botName);
            if (null === $command || !isset(TelegramCommandUseCaseInterface::COMMANDS_MAP[$command->command])) {
                return new TelegramUnsupportedUseCase(...$arguments);
            }

            $commandClass = TelegramCommandUseCaseInterface::COMMANDS_MAP[$command->command]['className'] ?? TelegramUnsupportedUseCase::class;

            return new $commandClass(...$arguments);
        }

        if ($update->getMessageObj()->hasBotMention($this->configPolicy->botName)) {
            return new TelegramBanStartProcedureUseCase(...$arguments);
        }

        if ($update->hasCallbackQueryData()) {
            return new TelegramBanVoteStartProcedureUseCase(...$arguments);
        }

        return new TelegramUnsupportedUseCase(...$arguments);
    }
}
