<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Exception;

final class TelegramApiException extends \RuntimeException
{
    public static function fromThrowable(string $action, \Throwable $previous): self
    {
        return new self(
            sprintf('Telegram API error during "%s": %s', $action, $previous->getMessage()),
            (int) $previous->getCode(),
            $previous
        );
    }

    public static function requestFailed(string $action, string $reason): self
    {
        return new self(
            sprintf('Telegram API request "%s" failed: %s', $action, $reason)
        );
    }
}
