<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Server;

use Laminas\ApiTools\Doctrine\Server\Service\DoctrineHydratorFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'service_manager'                        => [
        'abstract_factories' => [
            Resource\DoctrineResourceFactory::class,
        ],
        'factories'          => [
            'LaminasApiToolsDoctrineQueryProviderManager'
                => Query\Provider\Service\QueryProviderManagerFactory::class,
            'LaminasApiToolsDoctrineQueryCreateFilterManager'
                => Query\CreateFilter\Service\QueryCreateFilterManagerFactory::class,
        ],
    ],
    'hydrators'                              => [
        'abstract_factories' => [
            DoctrineHydratorFactory::class,
        ],
    ],
    'api-tools-doctrine-query-provider'      => [
        'aliases'   => [
            'default_odm' => Query\Provider\DefaultOdm::class,
            'default_orm' => Query\Provider\DefaultOrm::class,
        ],
        'factories' => [
            Query\Provider\DefaultOdm::class => InvokableFactory::class,
            Query\Provider\DefaultOrm::class => InvokableFactory::class,
        ],
    ],
    'api-tools-doctrine-query-create-filter' => [
        'aliases'   => [
            'default' => Query\CreateFilter\DefaultCreateFilter::class,
        ],
        'factories' => [
            Query\CreateFilter\DefaultCreateFilter::class => InvokableFactory::class,
        ],
    ],
    'view_manager'                           => [
        'template_path_stack' => [
            'api-tools-doctrine' => __DIR__ . '/../view',
        ],
    ],
];
