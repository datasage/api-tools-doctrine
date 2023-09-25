<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Server\Validator;

use Doctrine\ORM\EntityManager;
use DoctrineModule\Validator\NoObjectExists;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Stdlib\ArrayUtils;
use Psr\Container\ContainerInterface;

class NoObjectExistsFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array $options
     * @return NoObjectExists
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (isset($options['entity_class'])) {
            $objectRepository = $container
                ->get(EntityManager::class)
                ->getRepository($options['entity_class']);

            $options = ArrayUtils::merge($options, ['object_repository' => $objectRepository]);
        }

        return new NoObjectExists($options);
    }
}
