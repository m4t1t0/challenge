<?php

use App\Shared\Application\Command\CommandHandlerInterface;
use App\Shared\Application\Query\QueryHandlerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()
            ->autowire()       // inject dependencies automatically
            ->autoconfigure()  // register controllers/commands/handlers from attributes
            ->bind('bool $debug', '%kernel.debug%'); // for JsonExceptionSubscriber

    // CQRS handlers are tagged as Messenger handlers by their marker interface,
    // so adding a feature stays zero-config. Declared before load() so it
    // applies to the services registered below.
    $messengerHandler = ['bus' => 'messenger.bus.default', 'method' => 'handle'];
    $services->instanceof(CommandHandlerInterface::class)->tag('messenger.message_handler', $messengerHandler);
    $services->instanceof(QueryHandlerInterface::class)->tag('messenger.message_handler', $messengerHandler);

    // One service per class in src/, keyed by FQCN.
    $services->load('App\\', '../src/')
        ->exclude([
            '../src/DependencyInjection/',
            '../src/Kernel.php',
            // Domain models/value objects are created with `new`, never the container.
            '../src/*/Domain/',
        ]);

    // Bind domain ports to their Infra adapters here as modules are added, e.g.
    // $services->alias(SomePort::class, SomeAdapter::class);
};
