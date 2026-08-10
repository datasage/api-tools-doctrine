<?php

declare(strict_types=1);

namespace LaminasTestApiToolsDb\Entity;

use Doctrine\ORM\Mapping as ORM;
use LaminasTestApiToolsDb\Type\RevGenerator;

#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: 'rev')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: RevGenerator::class)]
    protected $id;

    #[ORM\Column(type: 'string', nullable: true)]
    protected $version;

    public function getId()
    {
        return $this->id;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version): void
    {
        $this->version = $version;
    }
}
