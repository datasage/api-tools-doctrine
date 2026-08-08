<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Server\Query\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Laminas\ApiTools\Rest\ResourceEvent;
use Override;

class DefaultOrm extends AbstractQueryProvider
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function createQuery(ResourceEvent $event, $entityClass, $parameters): QueryBuilder
    {
        /** @var EntityManager $em */
        $em           = $this->getObjectManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
            ->select('row')
            ->from($entityClass, 'row');

        return $queryBuilder;
    }
}
