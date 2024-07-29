<?php

declare(strict_types=1);

namespace App\Component\Telegram\Policy;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

readonly class TelegramConfigPolicy
{
    public const int JSON_OPTIONS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public function __construct(
        #[Autowire(param: 'app.secret')]
        public string $appSecret,
        #[Autowire(param: 'telegram.api_url')]
        public string $apiUrl,
        #[Autowire(param: 'telegram.bot_name')]
        public string $botName,
        #[Autowire(param: 'telegram.bot_token')]
        public string $botToken,
    ) {
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl . $this->botToken;
    }

    public function validateSecret(string $secret): void
    {
        if ($secret !== $this->appSecret) {
            throw new BadRequestException('Invalid secret');
        }
    }
}
