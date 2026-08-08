<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Server\Query\CreateFilter;

use Laminas\ApiTools\Rest\ResourceEvent;
use Override;

class DefaultCreateFilter extends AbstractCreateFilter
{
    /**
     * @param string $entityClass
     * @param array $data
     * @return array
     */
    #[Override]
    public function filter(ResourceEvent $event, $entityClass, $data)
    {
        return $data;
    }
}
