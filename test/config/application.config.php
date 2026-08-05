<?php

declare(strict_types=1);

use Laminas\I18n\Module;

return [
    'modules'                 => [
        'DoctrineModule',
        'DoctrineORMModule',
        'Laminas\ApiTools',
        'Laminas\ApiTools\Hal',
        'Laminas\ApiTools\ContentNegotiation',
        'Laminas\ApiTools\Rest',
        'Laminas\ApiTools\Rpc',
        'Laminas\ApiTools\Versioning',
        'Laminas\ApiTools\ApiProblem',
        'Laminas\ApiTools\Doctrine\Server',
        'LaminasTestApiToolsGeneral',
        'LaminasTestApiToolsDb',
        'LaminasTestApiToolsDbApi',
        Module::class,
    ],
    'module_listener_options' => [
        'config_glob_paths' => [
            __DIR__ . '/testing.config.php',
        ],
        'module_paths'      => [
            'LaminasTestApiToolsGeneral' => __DIR__ . '/../assets/module/LaminasTestApiToolsGeneral',
            'LaminasTestApiToolsDb'      => __DIR__ . '/../assets/module/LaminasTestApiToolsDb',
            'LaminasTestApiToolsDbApi'   => __DIR__ . '/../assets/module/LaminasTestApiToolsDbApi',
        ],
    ],
];
