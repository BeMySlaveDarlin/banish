<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Constants;

final class ChatDefaults
{
    public const string OPTION_BAN_VOTES_REQUIRED = 'ban_votes_required';
    public const string OPTION_DELETE_MESSAGE = 'delete_message';
    public const string OPTION_DELETE_ONLY = 'delete_only';
    public const string OPTION_MIN_MESSAGES_FOR_TRUST = 'min_messages_for_trust';
    public const string OPTION_BAN_EMOJI = 'ban_emoji';
    public const string OPTION_FORGIVE_EMOJI = 'forgive_emoji';
    public const string OPTION_ENABLE_REACTIONS = 'enable_reactions';

    public const int DEFAULT_VOTES_REQUIRED = 3;
    public const bool DEFAULT_DELETE_MESSAGES = true;
    public const bool DEFAULT_DELETE_ONLY = false;
    public const int DEFAULT_MIN_MESSAGES_FOR_TRUST = 5;
    public const bool DEFAULT_ENABLE_REACTIONS = false;
}
