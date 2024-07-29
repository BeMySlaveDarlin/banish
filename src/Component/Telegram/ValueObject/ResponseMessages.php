<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject;

class ResponseMessages
{
    public const string EMOJI_BAN = "🌚";
    public const string EMOJI_FORGIVE = "🌝";
    public const string MESSAGE_BAN_404 = "Ban request not found";
    public const string MESSAGE_BAN_API_ERROR = "User ban procedure failed. API error";
    public const string MESSAGE_BAN_PROCESSED = "User ban procedure processed";
    public const string MESSAGE_BAN_STARTED = "User ban procedure started";
    public const string MESSAGE_BOT_DISABLED = "Ban feature disabled for chat. Use /toggleBot to enable";
    public const string MESSAGE_BOT_STATUS = "Bot status *%s*";
    public const string MESSAGE_COMMAND_404 = "Command not found";
    public const string MESSAGE_HELLO = "Hello %s!\n";
    public const string MESSAGE_IS_PRIVATE_CHAT = "Private chat supports only /start and /help  command";
    public const string MESSAGE_NOT_SUPPORTED_CB = "Callback not supported";
    public const string MESSAGE_NOT_SUPPORTED_UPD = "Unsupported update type";
    public const string MESSAGE_NO_ACCESS = "Access denied";
    public const string MESSAGE_PROCESSED = "Processed";
    public const string MESSAGE_ADMIN_IS_IMMUNE = "Admin is immune";
    public const string MESSAGE_SPAM_404 = "User ban procedure failed. Spam message not found";
    public const string MESSAGE_TOGGLE_DELETE_SPAM = "Delete spam message after ban is set to *%s*";
    public const string MESSAGE_VOTE_MAX_LIMIT = "Max number of votes limit is set to *%s*";
    public const string START_BAN_PATTERN = "%s requested ban procedure on spammer %s\n";
    public const string VOTE_BAN_BUTTON_PATTERN = ResponseMessages::EMOJI_BAN . " Ban (%s)";
    public const string VOTE_BAN_PATTERN = "%s voted for *%s*\n";
    public const string VOTE_FORGIVE_BUTTON_PATTERN = ResponseMessages::EMOJI_FORGIVE . " Forgive (%s)";
}
