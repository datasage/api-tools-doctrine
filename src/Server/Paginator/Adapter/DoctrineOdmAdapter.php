<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Server\Paginator\Adapter;

use Doctrine\Odm\MongoDB\Query\Builder;
use Laminas\Paginator\Adapter\AdapterInterface;
use Override;

class DoctrineOdmAdapter implements AdapterInterface
{
    /** @var Builder $queryBuilder */
    protected $queryBuilder;

    /**
     * @param Builder $queryBuilder
     */
    public function __construct($queryBuilder)
    {
        $this->setQueryBuilder($queryBuilder);
    }

    /**
     * @param Builder $queryBuilder
     */
    public function setQueryBuilder($queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return Builder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param int $offset
     * @param int $itemCountPerPage
     * @return array
     */
    #[Override]
    public function getItems($offset, $itemCountPerPage)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->skip($offset);
        $queryBuilder->limit($itemCountPerPage);

        return $queryBuilder->getQuery()->execute()->toArray();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function count(): int
    {
        $queryBuilder = clone $this->getQueryBuilder();
        $queryBuilder->count();
        $queryBuilder->skip(0);
        $queryBuilder->limit(0);

        return $queryBuilder->getQuery()->execute();
    }
}
