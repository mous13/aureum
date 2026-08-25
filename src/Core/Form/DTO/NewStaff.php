<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Form\DTO;

use Citadel\Aureum\Core\Entity\HotelRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class NewStaff
{
    #[Assert\NotBlank(message: 'Enter the employee\'s name.')]
    #[Assert\Length(max: 255)]
    private string $name = '';

    #[Assert\NotBlank(message: 'Choose a username for them to sign in with.')]
    #[Assert\Length(min: 3, max: 32)]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9_.-]+$/',
        message: 'Use letters, numbers, dots, dashes and underscores only.',
    )]
    private string $username = '';

    #[Assert\NotBlank(message: 'Enter an email address.')]
    #[Assert\Email(message: 'That does not look like a valid email address.')]
    #[Assert\Length(max: 128)]
    private string $email = '';

    private ?string $timezone = null;

    /** @var Collection<int, HotelRole> */
    private Collection $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
    }

    /** @return Collection<int, HotelRole> */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /** @param Collection<int, HotelRole> $roles */
    public function setRoles(Collection $roles): void
    {
        $this->roles = $roles;
    }
}
