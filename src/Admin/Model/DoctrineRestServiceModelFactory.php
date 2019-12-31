<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-doctrine for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-doctrine/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-doctrine/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Doctrine\Admin\Model;

use Laminas\ApiTools\Admin\Exception;
use Laminas\ApiTools\Admin\Model\RpcServiceModelFactory;
use Laminas\ServiceManager\ServiceManager;

class DoctrineRestServiceModelFactory extends RpcServiceModelFactory
{
    const TYPE_DEFAULT = 'Laminas\ApiTools\Doctrine\Admin\Model\DoctrineRestServiceModel';

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Get service manager
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set service manager
     *
     * @param ServiceManager $serviceManager
     * @return DoctrineRestServiceModelFactory
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }


    /**
     * @param string $module
     * @param string $type
     * @return DoctrineRestServiceModel
     */
    public function factory($module, $type = self::TYPE_DEFAULT)
    {
        if (isset($this->models[$type])
            && isset($this->models[$type][$module])
        ) {
            // @codeCoverageIgnoreStart
            return $this->models[$type][$module];
        }
            // @codeCoverageIgnoreEnd

        $moduleName   = $this->normalizeModuleName($module);
        $config       = $this->configFactory->factory($module);
        $moduleEntity = $this->moduleModel->getModule($moduleName);

        $restModel = new DoctrineRestServiceModel($moduleEntity, $this->modules, $config);
        $restModel->getEventManager()->setSharedManager($this->sharedEventManager);
        $restModel->setServiceManager($this->getServiceManager());

        switch ($type) {
            case self::TYPE_DEFAULT:
                $this->models[$type][$module] = $restModel;

                return $restModel;
            // @codeCoverageIgnoreStart
            default:
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'Model of type "%s" does not exist or cannot be handled by this factory',
                        $type
                    )
                );
        }
            // @codeCoverageIgnoreEnd
    }
}
