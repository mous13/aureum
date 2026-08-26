<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Repository\SopCategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SopCategoryRepository::class)]
#[ORM\Table(name: 'aureum_sop_categories')]
class SopCategory implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    public function getHotel(): ?Hotel
    {
        return $this->hotel ?? null;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name ?? '';
    }
}
