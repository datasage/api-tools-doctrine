<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Admin\Controller;

use Laminas\ApiTools\Doctrine\Admin\Model\DoctrineAutodiscoveryModel;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DoctrineAutodiscoveryControllerFactory implements FactoryInterface
{
    /**
     * Create and return DoctrineAutodiscoveryController instance.
     *
     * @param string $requestedName
     * @param null|array $options
     * @return DoctrineAutodiscoveryController
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var DoctrineAutodiscoveryModel $model */
        $model = $container->get(DoctrineAutodiscoveryModel::class);

        return new DoctrineAutodiscoveryController($model);
    }
}
