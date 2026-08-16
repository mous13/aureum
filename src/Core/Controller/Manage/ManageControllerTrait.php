<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller\Manage;

use Citadel\Aureum\Core\Entity\Hotel;
use Symfony\Component\Form\FormInterface;

trait ManageControllerTrait
{
    protected function requireHotel(): Hotel
    {
        $hotel = $this->aureumService->getHotel();
        if ($hotel === null) {
            throw $this->createAccessDeniedException();
        }

        return $hotel;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createNamedForm(string $name, string $type, mixed $data, array $options = []): FormInterface
    {
        return $this->container->get('form.factory')->createNamed($name, $type, $data, $options);
    }
}
