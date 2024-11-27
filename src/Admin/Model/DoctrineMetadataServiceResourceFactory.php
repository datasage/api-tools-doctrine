<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Admin\Model;

use Laminas\ApiTools\Doctrine\Admin\Model\DoctrineMetadataServiceResource;
use Psr\Container\ContainerInterface;

class DoctrineMetadataServiceResourceFactory
{
    public function __invoke(ContainerInterface $container): DoctrineMetadataServiceResource
    {
        $instance = new DoctrineMetadataServiceResource();
        $instance->setServiceManager($container);

        return $instance;
    }
}
