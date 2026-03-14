<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\DependencyInjection;

use App\Infrastructure\Telegram\Attribute\AsTelegramCommand;
use App\Infrastructure\Telegram\Attribute\AsTelegramHandler;
use App\Infrastructure\Telegram\Dispatcher\CommandHandlerFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class TelegramCommandPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(CommandHandlerFactory::class)) {
            return;
        }

        $commandRegistryMap = [];
        $commandHandlerMap = [];
        $handlerReferences = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass() ?? $id;

            if (!str_starts_with($class, 'App\\')) {
                continue;
            }

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            $commandAttributes = $reflection->getAttributes(AsTelegramCommand::class);
            foreach ($commandAttributes as $attribute) {
                $instance = $attribute->newInstance();
                $commandRegistryMap[$instance->command] = $class;
            }

            $handlerAttributes = $reflection->getAttributes(AsTelegramHandler::class);
            foreach ($handlerAttributes as $attribute) {
                $instance = $attribute->newInstance();
                $commandHandlerMap[$instance->commandClass] = $class;
                $handlerReferences[$class] = new Reference($class);
            }
        }

        $factoryDefinition = $container->getDefinition(CommandHandlerFactory::class);

        $serviceLocator = ServiceLocatorTagPass::register($container, $handlerReferences);

        $factoryDefinition->setArgument('$handlerLocator', $serviceLocator);
        $factoryDefinition->setArgument('$commandRegistryMap', $commandRegistryMap);
        $factoryDefinition->setArgument('$commandHandlerMap', $commandHandlerMap);
    }
}
