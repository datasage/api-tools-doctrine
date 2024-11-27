<?php

declare(strict_types=1);

use Laminas\Cache\Storage\Adapter\Memory;

return [
    'modules'                 => [
        'DoctrineModule',
        'DoctrineMongoODMModule',
        'Laminas\Cache',
        Memory::class,
        'Laminas\ApiTools',
        'Laminas\ApiTools\Hal',
        'Laminas\ApiTools\ContentNegotiation',
        'Laminas\ApiTools\Rest',
        'Laminas\ApiTools\Rpc',
        'Laminas\ApiTools\Versioning',
        'Laminas\ApiTools\ApiProblem',
        'Laminas\ApiTools\Doctrine\Server',
        'LaminasTestApiToolsGeneral',
        'LaminasTestApiToolsDbMongo',
        'LaminasTestApiToolsDbMongoApi',
    ],
    'module_listener_options' => [
        'config_glob_paths' => [
            __DIR__ . '/local.php',
        ],
        'module_paths'      => [
            'LaminasTestApiToolsGeneral'    => __DIR__ . '/../../assets/module/LaminasTestApiToolsGeneral',
            'LaminasTestApiToolsDbMongo'    => __DIR__ . '/../../assets/module/LaminasTestApiToolsDbMongo',
            'LaminasTestApiToolsDbMongoApi' => __DIR__ . '/../../assets/module/LaminasTestApiToolsDbMongoApi',
        ],
    ],
];
