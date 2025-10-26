<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramMessageChat
{
    public ?int $id = null;
    public ?string $type = null;
    public ?string $title = null;
    public ?string $username = null;
    public ?string $last_name = null;
    public ?string $first_name = null;

    public function isEmpty(): bool
    {
        return $this->id === null;
    }

    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    public function getAlias(): string
    {
        if (null !== $this->username) {
            return $this->username;
        }
        if (null !== $this->title) {
            return $this->title;
        }

        $firstName = $this->first_name ?? '';
        $lastName = $this->last_name ?? '';
        $alias = trim("$firstName $lastName");

        return $alias ?: 'Chat';
    }
}
