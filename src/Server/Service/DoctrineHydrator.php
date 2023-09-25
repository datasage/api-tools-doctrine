<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Doctrine\Server\Service;

use Laminas\Hydrator\HydratorInterface;

class DoctrineHydrator implements HydratorInterface
{
    /** @var HydratorInterface */
    protected $extractService;

    /** @var HydratorInterface|\Doctrine\ODM\MongoDB\Hydrator\HydratorInterface */
    protected $hydrateService;

    /**
     * @param HydratorInterface $extractService
     * @param HydratorInterface|\Doctrine\ODM\MongoDB\Hydrator\HydratorInterface $hydrateService
     */
    public function __construct($extractService, $hydrateService)
    {
        $this->extractService = $extractService;
        $this->hydrateService = $hydrateService;
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
     *
     * @return array
     */
    public function extract(object $object): array
    {
        return $this->extractService->extract($object);
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @param array $data
     * @return object
     */
    public function hydrate(array $data, object $object)
    {
        // Laminas hydrator:
        if ($this->hydrateService instanceof HydratorInterface) {
            return $this->hydrateService->hydrate($data, $object);
        }

        // Doctrine hydrator: (parameters switched)
        return $this->hydrateService->hydrate($object, $data);
    }
}
