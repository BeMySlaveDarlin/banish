<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject\Bot;

class TelegramWebHookInfo
{
    public ?string $url = null;
    public ?string $ip_address = null;
    public bool $has_custom_certificate;
    public int $pending_update_count = 0;
    public int $max_connections = 0;
}
