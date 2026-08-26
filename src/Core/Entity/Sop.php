<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\SopStatus;
use Citadel\Aureum\Core\Repository\SopRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SopRepository::class)]
#[ORM\Table(name: 'aureum_sops')]
#[ORM\Index(name: 'idx_sop_hotel_status', columns: ['hotel_id', 'status'])]
class Sop implements HotelOwnedInterface
{
    use IdentifiableEntityTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Hotel $hotel;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private string $title = '';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SopCategory $category = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $body = '';

    #[ORM\Column(type: 'text')]
    private string $bodyText = '';

    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(60)]
    private ?int $recheckMonths = null;

    #[ORM\Column(type: 'string', length: 50, enumType: SopStatus::class)]
    private SopStatus $status = SopStatus::DRAFT;

    /** @var Collection<int, HotelRole> */
    #[ORM\ManyToMany(targetEntity: HotelRole::class)]
    #[ORM\JoinTable(name: 'aureum_sop_audience')]
    private Collection $audience;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $createdBy;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Employee $updatedBy = null;

    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private DateTime $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $publishedAt = null;

    public function __construct()
    {
        $this->audience = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getHotel(): Hotel
    {
        return $this->hotel;
    }

    public function setHotel(Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title ?? '';
    }

    public function getCategory(): ?SopCategory
    {
        return $this->category;
    }

    public function setCategory(?SopCategory $category): void
    {
        $this->category = $category;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body ?? '';
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $this->body)), ENT_QUOTES | ENT_HTML5);

        $this->bodyText = trim((string)preg_replace('/\s+/u', ' ', $text));
    }

    public function getBodyText(): string
    {
        return $this->bodyText;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function bumpVersion(): void
    {
        $this->version++;
    }

    public function getRecheckMonths(): ?int
    {
        return $this->recheckMonths;
    }

    public function setRecheckMonths(?int $recheckMonths): void
    {
        $this->recheckMonths = $recheckMonths;
    }

    public function getStatus(): SopStatus
    {
        return $this->status;
    }

    public function setStatus(SopStatus $status): void
    {
        $this->status = $status;
    }

    public function publish(): void
    {
        $this->status = SopStatus::PUBLISHED;
        $this->publishedAt ??= new DateTime();
    }

    /** @return Collection<int, HotelRole> */
    public function getAudience(): Collection
    {
        return $this->audience;
    }

    public function addAudience(HotelRole $role): void
    {
        if (!$this->audience->contains($role)) {
            $this->audience->add($role);
        }
    }

    public function removeAudience(HotelRole $role): void
    {
        $this->audience->removeElement($role);
    }

    public function getCreatedBy(): Employee
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Employee $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getUpdatedBy(): ?Employee
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?Employee $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getPublishedAt(): ?DateTime
    {
        return $this->publishedAt;
    }
}
