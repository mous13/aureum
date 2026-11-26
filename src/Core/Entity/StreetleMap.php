<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\StreetleMapRepository;
use Citadel\Aureum\Core\Service\Streetle\StreetleBounds;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: StreetleMapRepository::class)]
#[ORM\Table(name: 'aureum_streetle_maps')]
class StreetleMap implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private Hotel $hotel;

    #[ORM\Column(type: 'float')]
    private float $west;

    #[ORM\Column(type: 'float')]
    private float $south;

    #[ORM\Column(type: 'float')]
    private float $east;

    #[ORM\Column(type: 'float')]
    private float $north;

    /** @var array<string, list<mixed>> */
    #[ORM\Column(type: 'json')]
    private array $basemap = [];

    #[ORM\Column(type: 'integer')]
    private int $streetCount = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $importedAt;

    public function __construct(Hotel $hotel, StreetleBounds $bounds)
    {
        $this->hotel = $hotel;
        $this->setBounds($bounds);
        $this->importedAt = new DateTimeImmutable();
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function getBounds(): StreetleBounds
    {
        return new StreetleBounds($this->west, $this->south, $this->east, $this->north);
    }

    public function setBounds(StreetleBounds $bounds): void
    {
        $this->west = $bounds->west;
        $this->south = $bounds->south;
        $this->east = $bounds->east;
        $this->north = $bounds->north;
    }

    /** @return array<string, list<mixed>> */
    public function getBasemap(): array
    {
        return $this->basemap;
    }

    /**
     * @param array<string, list<mixed>> $basemap
     */
    public function replaceContents(array $basemap, int $streetCount, DateTimeImmutable $importedAt): void
    {
        $this->basemap = $basemap;
        $this->streetCount = $streetCount;
        $this->importedAt = $importedAt;
    }

    public function getStreetCount(): int
    {
        return $this->streetCount;
    }

    public function getImportedAt(): DateTimeImmutable
    {
        return $this->importedAt;
    }
}
