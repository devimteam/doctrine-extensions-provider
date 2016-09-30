<?php

namespace Devim\Provider\DoctrineExtensionsServiceProvider\EventSubscriber;

use Devim\Provider\DoctrineExtensionsServiceProvider\Command\CreateDatabaseDoctrineCommand;
use Devim\Provider\DoctrineExtensionsServiceProvider\Command\DropDatabaseDoctrineCommand;
use Isolate\ConsoleServiceProvider\Console\ConsoleEvent;
use Isolate\ConsoleServiceProvider\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleEventSubscriber implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::INIT => ['onConsoleInit'],
        ];
    }

    /**
     * @param ConsoleEvent $event
     */
    public function onConsoleInit(ConsoleEvent $event)
    {
        $console = $event->getApp();
        $console->addCommands([
            new CreateDatabaseDoctrineCommand(),
            new DropDatabaseDoctrineCommand(),
        ]);
    }
}
