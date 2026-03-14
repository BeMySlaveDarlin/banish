<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Routing\Registry\RouteRegistryInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class Router
{
    /** @var array<int, RouteRegistryInterface> */
    private array $registries;

    /**
     * @param iterable<RouteRegistryInterface> $registries
     */
    public function __construct(
        private readonly string $botName,
        #[TaggedIterator('app.route_registry')]
        iterable $registries
    ) {
        $sorted = iterator_to_array($registries);
        usort($sorted, static fn (RouteRegistryInterface $a, RouteRegistryInterface $b): int => $b::getPriority() <=> $a::getPriority());
        $this->registries = $sorted;
    }

    public function route(TelegramUpdate $update): string
    {
        foreach ($this->registries as $registry) {
            if ($registry->matches($update, $this->botName)) {
                return $registry->getCommand($update, $this->botName);
            }
        }

        return UnsupportedCommand::class;
    }
}
