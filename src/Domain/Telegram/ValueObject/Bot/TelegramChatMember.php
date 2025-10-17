<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject\Bot;

use App\Domain\Telegram\ValueObject\TelegramMessageFrom;

class TelegramChatMember
{
    public const string CHAT_MEMBER_OWNER = 'creator';
    public const string CHAT_MEMBER_ADMIN = 'administrator';
    public const string CHAT_MEMBER_MEMBER = 'member';
    public const string CHAT_MEMBER_RESTRICTED = 'restricted';
    public const string CHAT_MEMBER_LEFT = 'left';
    public const string CHAT_MEMBER_BANNED = 'kicked';

    public TelegramMessageFrom $user;
    public string $status = '';
    public bool $can_be_edited = false;
    public bool $can_change_info = true;
    public bool $can_delete_messages = true;
    public bool $can_invite_users = true;
    public bool $can_restrict_members = true;
    public bool $can_pin_messages = true;
    public bool $can_promote_members = true;

    public function isAdmin(): bool
    {
        return in_array($this->status, [self::CHAT_MEMBER_OWNER, self::CHAT_MEMBER_ADMIN]);
    }
}
