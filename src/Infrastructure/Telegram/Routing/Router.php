<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Routing\Registry\RouteRegistryInterface;

final class Router
{
    /** @var array<int, RouteRegistryInterface> */
    private array $registries;

    /**
     * @param iterable<int, RouteRegistryInterface> $registries
     */
    public function __construct(
        private readonly string $botName,
        iterable $registries
    ) {
        $this->registries = iterator_to_array($registries);
    }

    public function route(TelegramUpdate $update): string
    {
        /** @var RouteRegistryInterface $registry */
        foreach ($this->registries as $registry) {
            if ($registry->matches($update, $this->botName)) {
                return $registry->getCommand($update, $this->botName);
            }
        }

        return UnsupportedCommand::class;
    }
}
