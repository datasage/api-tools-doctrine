<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Server\Service;

use Doctrine\Laminas\Hydrator;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\Persistence\ObjectManager;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use Laminas\Hydrator\AbstractHydrator;
use Laminas\Hydrator\Filter\FilterComposite;
use Laminas\Hydrator\Filter\FilterEnabledInterface;
use Laminas\Hydrator\Filter\FilterInterface;
use Laminas\Hydrator\NamingStrategy\NamingStrategyEnabledInterface;
use Laminas\Hydrator\NamingStrategy\NamingStrategyInterface;
use Laminas\Hydrator\Strategy\StrategyEnabledInterface;
use Laminas\Hydrator\Strategy\StrategyInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function is_array;
use function sprintf;

class DoctrineHydratorFactory implements AbstractFactoryInterface
{
    public const FACTORY_NAMESPACE = 'doctrine-hydrator';

    public const string OBJECT_MANAGER_TYPE_ORM = 'ORM';

    /**
     * Cache of canCreate lookups.
     *
     * @var array
     */
    protected $lookupCache = [];

    /**
     * Determine if we can create a service with name.
     *
     * @param string             $requestedName
     * @return bool
     * @throws ServiceNotFoundException
     */
    #[Override]
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (array_key_exists($requestedName, $this->lookupCache)) {
            return $this->lookupCache[$requestedName];
        }

        if (! $container->has('config')) {
            return false;
        }

        // Validate object is set
        $config    = $container->get('config');
        $namespace = self::FACTORY_NAMESPACE;
        if (
            ! isset($config[$namespace])
            || ! is_array($config[$namespace])
            || ! isset($config[$namespace][$requestedName])
        ) {
            $this->lookupCache[$requestedName] = false;

            return false;
        }

        // Validate object manager
        $config = $config[$namespace];
        if (! isset($config[$requestedName]) || ! isset($config[$requestedName]['object_manager'])) {
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid "object_manager" is specified for hydrator %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }

        // Validate object class
        if (! isset($config[$requestedName]['entity_class'])) {
            throw new ServiceNotFoundException(sprintf(
                '%s requires that a valid "entity_class" is specified for hydrator %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }

        $this->lookupCache[$requestedName] = true;

        return true;
    }

    /**
     * Create and return the database-connected resource.
     *
     * @param string             $requestedName
     * @param null|array         $options
     * @return DoctrineHydrator
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        $config = $config[self::FACTORY_NAMESPACE][$requestedName];

        $objectManager = $this->loadObjectManager($container, $config);

        $extractService = null;
        $hydrateService = null;

        $useCustomHydrator = array_key_exists('hydrator', $config);

        if ($useCustomHydrator) {
            try {
                $extractService = $container->build($config['hydrator'], $config);
            } catch (ServiceNotFoundException) {
                $extractService = $container->get($config['hydrator']);
            }

            $hydrateService = $extractService;
        }

        // Use DoctrineModuleHydrator by default
        if (! isset($extractService, $hydrateService)) {
            $doctrineModuleHydrator = $this->loadDoctrineModuleHydrator($container, $config, $objectManager);
            $extractService         = $extractService ?: $doctrineModuleHydrator;
            $hydrateService         = $hydrateService ?: $doctrineModuleHydrator;
        }

        $this->configureHydrator($extractService, $container, $config, $objectManager);
        $this->configureHydrator($hydrateService, $container, $config, $objectManager);

        return new DoctrineHydrator($extractService, $hydrateService);
    }

    /**
     * @return ObjectManager
     * @throws ServiceNotCreatedException
     */
    protected function loadObjectManager(ContainerInterface $container, array $config)
    {
        if (! $container->has($config['object_manager'])) {
            throw new ServiceNotCreatedException('The object_manager could not be found.');
        }

        return $container->get($config['object_manager']);
    }

    /**
     * @param ObjectManager      $objectManager
     */
    protected function loadDoctrineModuleHydrator(
        ContainerInterface $container,
        array $config,
        $objectManager
    ): DoctrineObject {
        return new Hydrator\DoctrineObject($objectManager, $config['by_value']);
    }

    /**
     * @param AbstractHydrator   $hydrator
     * @param array              $config
     * @param ObjectManager      $objectManager
     * @throws ServiceNotCreatedException
     */
    public function configureHydrator($hydrator, ContainerInterface $container, $config, $objectManager): void
    {
        $this->configureHydratorFilters($hydrator, $container, $config, $objectManager);
        $this->configureHydratorStrategies($hydrator, $container, $config, $objectManager);
        $this->configureHydratorNamingStrategy($hydrator, $container, $config, $objectManager);
    }

    /**
     * @param AbstractHydrator   $hydrator
     * @param ObjectManager      $objectManager
     * @throws ServiceNotCreatedException
     */
    public function configureHydratorNamingStrategy(
        $hydrator,
        ContainerInterface $container,
        array $config,
        $objectManager
    ): void {
        if (! $hydrator instanceof NamingStrategyEnabledInterface || ! isset($config['naming_strategy'])) {
            return;
        }

        $namingStrategyKey = $config['naming_strategy'];
        if (! $container->has($namingStrategyKey)) {
            throw new ServiceNotCreatedException(sprintf('Invalid naming strategy %s.', $namingStrategyKey));
        }

        $namingStrategy = $container->get($namingStrategyKey);
        if (! $namingStrategy instanceof NamingStrategyInterface) {
            throw new ServiceNotCreatedException(
                sprintf('Invalid naming strategy class %s', $namingStrategy::class)
            );
        }

        // Attach object manager:
        if ($namingStrategy instanceof ObjectManagerAwareInterface) {
            $namingStrategy->setObjectManager($objectManager);
        }

        $hydrator->setNamingStrategy($namingStrategy);
    }

    /**
     * @param AbstractHydrator   $hydrator
     * @param array              $config
     * @param ObjectManager      $objectManager
     * @throws ServiceNotCreatedException
     */
    protected function configureHydratorStrategies(
        $hydrator,
        ContainerInterface $container,
        $config,
        $objectManager
    ): void {
        if (
            ! $hydrator instanceof StrategyEnabledInterface
            || ! isset($config['strategies'])
            || ! is_array($config['strategies'])
        ) {
            return;
        }

        foreach ($config['strategies'] as $field => $strategyKey) {
            if (! $container->has($strategyKey)) {
                throw new ServiceNotCreatedException(sprintf('Invalid strategy %s for field %s', $strategyKey, $field));
            }

            $strategy = $container->get($strategyKey);
            if (! $strategy instanceof StrategyInterface) {
                throw new ServiceNotCreatedException(
                    sprintf('Invalid strategy class %s for field %s', $strategy::class, $field)
                );
            }

            // Attach object manager:
            if ($strategy instanceof ObjectManagerAwareInterface) {
                $strategy->setObjectManager($objectManager);
            }

            $hydrator->addStrategy($field, $strategy);
        }
    }

    /**
     * Add filters to the Hydrator based on a predefined configuration format, if specified.
     *
     * @param AbstractHydrator   $hydrator
     * @param array              $config
     * @param ObjectManager      $objectManager
     * @throws ServiceNotCreatedException
     */
    protected function configureHydratorFilters($hydrator, ContainerInterface $container, $config, $objectManager): void
    {
        if (
            ! $hydrator instanceof FilterEnabledInterface
            || ! isset($config['filters'])
            || ! is_array($config['filters'])
        ) {
            return;
        }

        foreach ($config['filters'] as $name => $filterConfig) {
            $conditionMap = [
                'and' => FilterComposite::CONDITION_AND,
                'or'  => FilterComposite::CONDITION_OR,
            ];
            $condition    = isset($filterConfig['condition']) ?
                $conditionMap[$filterConfig['condition']] :
                FilterComposite::CONDITION_OR;

            $filterService = $filterConfig['filter'];
            if (! $container->has($filterService)) {
                throw new ServiceNotCreatedException(
                    sprintf('Invalid filter %s for field %s: service does not exist', $filterService, $name)
                );
            }

            $filterService = $container->get($filterService);
            if (! $filterService instanceof FilterInterface) {
                throw new ServiceNotCreatedException(
                    sprintf('Filter service %s must implement FilterInterface', $filterService::class)
                );
            }

            if ($filterService instanceof ObjectManagerAwareInterface) {
                $filterService->setObjectManager($objectManager);
            }
            $hydrator->addFilter($name, $filterService, $condition);
        }
    }
}
