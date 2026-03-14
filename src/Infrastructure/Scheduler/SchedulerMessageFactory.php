<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler;

use App\Infrastructure\Scheduler\Admin\CleanupAdminSessionsMessage;
use App\Infrastructure\Scheduler\Common\RefreshDbPartitionsMessage;
use App\Infrastructure\Scheduler\Telegram\ClearBotMessagesMessage;

final class SchedulerMessageFactory
{
    /** @var array<string, true> */
    private const array ALLOWED_MESSAGES = [
        RefreshDbPartitionsMessage::class => true,
        ClearBotMessagesMessage::class => true,
        CleanupAdminSessionsMessage::class => true,
    ];

    /**
     * @param array<int|string, mixed> $options
     */
    public static function create(string $messageClass, array $options = []): object
    {
        if (!isset(self::ALLOWED_MESSAGES[$messageClass])) {
            throw new \InvalidArgumentException(
                sprintf('Message class "%s" is not in the allowed list', $messageClass)
            );
        }

        if (!class_exists($messageClass)) {
            throw new \InvalidArgumentException(
                sprintf('Message class "%s" does not exist', $messageClass)
            );
        }

        return new $messageClass();
    }
}
