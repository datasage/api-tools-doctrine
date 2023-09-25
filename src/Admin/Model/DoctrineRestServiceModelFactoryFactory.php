<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Admin\Model;

use Laminas\ApiTools\Admin\Model\ModuleModel;
use Laminas\ApiTools\Admin\Model\ModulePathSpec;
use Laminas\ApiTools\Configuration\ConfigResourceFactory;
use Laminas\ApiTools\Doctrine\Admin\Model\DoctrineRestServiceModelFactory;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Psr\Container\ContainerInterface;

use function sprintf;

class DoctrineRestServiceModelFactoryFactory
{
    public function __invoke(ContainerInterface $container): DoctrineRestServiceModelFactory
    {
        if (
            ! $container->has(ModulePathSpec::class)
            || ! $container->has(ModuleModel::class)
            || ! $container->has(ConfigResourceFactory::class)
            || ! $container->has('SharedEventManager')
        ) {
            throw new ServiceNotCreatedException(sprintf(
                '%s is missing one or more dependencies from Laminas\ApiTools\Configuration',
                DoctrineRestServiceModelFactory::class
            ));
        }

        $sharedEvents = $container->get('SharedEventManager');
        $this->attachSharedListeners($sharedEvents);

        $instance = new DoctrineRestServiceModelFactory(
            $container->get(ModulePathSpec::class),
            $container->get(ConfigResourceFactory::class),
            $sharedEvents,
            $container->get(ModuleModel::class)
        );
        $instance->setServiceManager($container);

        return $instance;
    }

    /**
     * Attach shared listeners to the DoctrineRestServiceModel.
     */
    private function attachSharedListeners(SharedEventManagerInterface $sharedEvents): void
    {
        $sharedEvents->attach(
            DoctrineRestServiceModel::class,
            'fetch',
            [DoctrineRestServiceModel::class, 'onFetch']
        );
    }
}
