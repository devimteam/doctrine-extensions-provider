<?php

namespace Devim\Provider\DoctrineExtensionsServiceProvider;

use Devim\Provider\DoctrineExtensionsServiceProvider\EventSubscriber\ConsoleEventSubscriber;
use Devim\Provider\DoctrineExtensionsServiceProvider\Type\JsonbArrayType;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Gedmo\DoctrineExtensions;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DoctrineExtensionsServiceProvider.
 */
class DoctrineExtendServiceProvider implements ServiceProviderInterface, BootableProviderInterface, EventListenerProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     *
     * @throws \RuntimeException
     */
    public function register(Container $container)
    {
        DoctrineExtensions::registerAnnotations();

        if (!isset($container['db.event_manager'])) {
            throw new \RuntimeException('Doctrine database event manager not found');
        }

        $container['orm.extend.subscribers'] = [];
        $container['orm.extend.listeners'] = [];
        $container['orm.extend.filters'] = [];
        $container['orm.extend.mapping_types'] = [];
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param Application $app
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \InvalidArgumentException
     */
    public function boot(Application $app)
    {
        /** @var EventManager $eventManager */
        $eventManager = $app['db.event_manager'];

        /** @var Configuration $ormConfig */
        $ormConfig = $app['orm.em.config'];

        /** @var EntityManager $entityManager */
        $entityManager = $app['orm.em'];

        foreach ($app['orm.extend.subscribers'] as $subscriber) {
            $eventManager->addEventSubscriber($subscriber);
        }

        foreach ($app['orm.extend.listeners'] as $event => $listener) {
            $eventManager->addEventListener($event, $listener);
        }

        foreach ($app['orm.extend.filters'] as $filterName => $filterData) {
            $ormConfig->addFilter($filterData['filter'], $filterData['class']);
            $entityManager->getFilters()->enable($filterData['filter']);
        }

        if (Type::hasType('jsonb')) {
            Type::overrideType('jsonb', JsonbArrayType::class);
        } else {
            Type::addType('jsonb', JsonbArrayType::class);
        }

        foreach ($app['orm.extend.mapping_types'] as $type => $mappingType) {
            $entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping($type, $mappingType);
        }
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber(new ConsoleEventSubscriber());
    }
}
