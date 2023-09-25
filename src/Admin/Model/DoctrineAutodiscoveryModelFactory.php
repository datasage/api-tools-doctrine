<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Admin\Model;

use Laminas\ApiTools\Doctrine\Admin\Model\DoctrineAutodiscoveryModel;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Psr\Container\ContainerInterface;

use function sprintf;

class DoctrineAutodiscoveryModelFactory
{
    public function __invoke(ContainerInterface $container): DoctrineAutodiscoveryModel
    {
        if (! $container->has('config')) {
            throw new ServiceNotCreatedException(sprintf(
                'Cannot create %s service because config service is not present',
                DoctrineAutodiscoveryModel::class
            ));
        }

        $instance = new DoctrineAutodiscoveryModel($container->get('config'));
        $instance->setServiceLocator($container);

        return $instance;
    }
}
