<?php

declare(strict_types=1);

namespace LaminasTestApiToolsDbApi;

use Laminas\Loader\StandardAutoloader;

class Module
{
    /**
     * @return array
     *
     * @psalm-return array<empty, empty>
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    /**
     * @return string[][][]
     * @psalm-return array<string, array<string, array<string, string>>>
     */
    public function getAutoloaderConfig(): array
    {
        return [
            StandardAutoloader::class => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__,
                ],
            ],
        ];
    }
}
