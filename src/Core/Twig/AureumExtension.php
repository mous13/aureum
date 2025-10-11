<?php

namespace Citadel\Aureum\Core\Twig;

use Citadel\Aureum\Core\MenuBuilder\AureumMenuManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Citadel\Aureum\Core\Repository\EmployeeRepository;

class AureumExtension extends AbstractExtension
{
    public function __construct(
        private readonly AureumMenuManager $aureumMenuManager,
        private readonly EmployeeRepository $employeeRepository,
    ){
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('aureum_menu', $this->aureumMenuManager->getMenu(...)),
            new TwigFunction('employee', [$this, 'getEmployee'])
        ];
    }

    public function getEmployee(User $user): ?Employee
    {
        return $this->employeeRepository->findOneBy(['user' => $user]);
    }

}