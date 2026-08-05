<?php

declare(strict_types=1);

namespace LaminasTestApiToolsDb\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;

use function md5;
use function mt_getrandmax;
use function random_int;
use function strrev;
use function time;

class RevGenerator extends AbstractIdGenerator
{
    /**
     * ORM 2.11 introduced generateId() and deprecated generate(); ORM 3 removed
     * generate() entirely. Implementing only generateId() satisfies both majors.
     *
     * $entity is left untyped on purpose: ORM 2 declares it untyped and ORM 3 as
     * ?object, so an untyped parameter is compatible with both. Typing it would
     * narrow ORM 2's signature, which PHP rejects.
     *
     * @param object|null $entity
     */
    public function generateId(EntityManagerInterface $em, $entity): string
    {
        do {
            $value = md5(time() . random_int(0, mt_getrandmax()));
        } while ($value === strrev($value));

        return $value;
    }
}
