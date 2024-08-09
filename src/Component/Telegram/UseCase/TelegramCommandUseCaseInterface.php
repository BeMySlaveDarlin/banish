<?php

declare(strict_types=1);

namespace App\Component\Telegram\UseCase;

interface TelegramCommandUseCaseInterface
{
    public const string COMMAND_START = '/start';
    public const string COMMAND_HELP = '/help';
    public const string COMMAND_TOGGLE = '/toggleBot';
    public const string COMMAND_SET_VOTES_LIMIT = '/votesLimit';
    public const string COMMAND_SET_DELETE_MESSAGE = '/toggleDeleteMessage';

    public const array COMMANDS_MAP = [
        self::COMMAND_HELP => [
            'className' => TelegramCommandHelpUseCase::class,
            'description' => "List of available commands.\nUsage: `/help`\n",
        ],
        self::COMMAND_TOGGLE => [
            'className' => TelegramCommandToggleBotUseCase::class,
            'description' => "Turn on/off bot for group.\nDefault: Off.\nUsage: `/toggleBot`\n",
        ],
        self::COMMAND_SET_VOTES_LIMIT => [
            'className' => TelegramCommandSetVotesLimitUseCase::class,
            'description' => "Set max number of votes to accept or revoke ban.\nDefault: 3.\nUsage: `/votesLimit 5`\n",
        ],
        self::COMMAND_SET_DELETE_MESSAGE => [
            'className' => TelegramCommandToggleDeleteMessageUseCase::class,
            'description' => "Delete spam message after ban accepted.\nDefault: On.\nUsage: `/toggleDeleteMessage`\n",
        ],
    ];
}
