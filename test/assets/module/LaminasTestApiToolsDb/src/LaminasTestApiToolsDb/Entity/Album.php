<?php

declare(strict_types=1);

namespace LaminasTestApiToolsDb\Entity;

use DateTime;
use InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Album
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return static
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $createdAt;

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return static
     */
    public function setCreatedAt(DateTime $value): self
    {
        $this->createdAt = $value;

        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Artist::class, inversedBy: 'album')]
    #[ORM\JoinColumn(name: 'artist_id', referencedColumnName: 'id')]
    protected $artist;

    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * @return static
     */
    public function setArtist(Artist $value): self
    {
        $this->artist = $value;

        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', nullable: true)]
    protected $album;

    /**
     * Parent Album
     *
     * @return null|Album
     */
    public function getAlbum()
    {
        return $this->album;
    }

    /**
     * Parent Album
     *
     * @param null|Album $album
     * @return $this
     */
    public function setAlbum($album): static
    {
        if (null !== $album && ! $album instanceof Album) {
            throw new InvalidArgumentException('Invalid album argument');
        }
        $this->album = $album;
        return $this;
    }
}
