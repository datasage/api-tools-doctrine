<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Server\Service;

use Laminas\Hydrator\HydratorInterface;
use Override;

class DoctrineHydrator implements HydratorInterface
{
    /**
     * @param HydratorInterface $extractService
     * @param HydratorInterface $hydrateService
     */
    public function __construct(protected $extractService, protected $hydrateService)
    {
    }

    /**
     * @return HydratorInterface
     */
    public function getExtractService()
    {
        return $this->extractService;
    }

    /**
     * @return HydratorInterface
     */
    public function getHydrateService()
    {
        return $this->hydrateService;
    }

    /**
     * Extract values from an object.
     */
    #[Override]
    public function extract(object $object): array
    {
        return $this->extractService->extract($object);
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @return object
     */
    #[Override]
    public function hydrate(array $data, object $object)
    {
        return $this->hydrateService->hydrate($data, $object);
    }
}
