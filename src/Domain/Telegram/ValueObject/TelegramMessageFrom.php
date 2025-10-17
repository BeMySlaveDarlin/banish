<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramMessageFrom
{
    public ?int $id = null;
    public ?string $username = null;
    public ?string $first_name = null;
    public ?string $last_name = null;
    public string $language_code;
    public bool $is_bot = false;

    public function isEmpty(): bool
    {
        return $this->id === null;
    }

    public function getAlias(): string
    {
        if (null !== $this->username) {
            return '@' . $this->username;
        }

        $firstName = $this->first_name;
        $lastName = $this->last_name;

        return trim("$firstName $lastName") ?? 'User';
    }
}
