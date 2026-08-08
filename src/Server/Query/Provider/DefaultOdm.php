<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Server\Query\Provider;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Laminas\ApiTools\Doctrine\Server\Paginator\Adapter\DoctrineOdmAdapter;
use Laminas\ApiTools\Rest\ResourceEvent;
use Override;

class DefaultOdm extends AbstractQueryProvider
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function createQuery(ResourceEvent $event, $entityClass, $parameters): Builder
    {
        /** @var DocumentManager $documentManager */
        $documentManager = $this->getObjectManager();
        $queryBuilder    = $documentManager->createQueryBuilder();
        $queryBuilder->find($entityClass);

        return $queryBuilder;
    }

    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     * @param Builder $queryBuilder
     */
    #[Override]
    public function getPaginatedQuery($queryBuilder): DoctrineOdmAdapter
    {
        return new DoctrineOdmAdapter($queryBuilder);
    }

    /**
     * @param class-string $entityClass
     * @return int
     */
    #[Override]
    public function getCollectionTotal($entityClass)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $queryBuilder->find($entityClass);
        return $queryBuilder->getQuery()->execute()->count();
    }
}
