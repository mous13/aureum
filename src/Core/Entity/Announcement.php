<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Enum\AnnouncementSeverity;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Repository\AnnouncementRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: AnnouncementRepository::class)]
#[ORM\Table(name: 'aureum_announcements')]
class Announcement
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(length: 50, enumType: Module::class)]
    private Module $module;

    #[ORM\Column(length: 20, enumType: AnnouncementSeverity::class)]
    private AnnouncementSeverity $severity = AnnouncementSeverity::INFO;

    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getModule(): Module
    {
        return $this->module;
    }

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    public function getSeverity(): AnnouncementSeverity
    {
        return $this->severity;
    }

    public function setSeverity(AnnouncementSeverity $severity): void
    {
        $this->severity = $severity;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
