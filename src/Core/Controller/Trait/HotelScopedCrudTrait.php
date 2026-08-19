<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller\Trait;

use Citadel\Aureum\Core\Entity\HotelOwnedInterface;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

trait HotelScopedCrudTrait
{
    abstract protected function getAureumService(): AureumService;

    #[Route('/{identifier}/edit', name: '_edit')]
    public function edit(Request $request, string $identifier): Response
    {
        $this->denyUnlessSameHotel($identifier);

        return parent::edit($request, $identifier);
    }

    #[Route('/{identifier}/delete', name: '_delete', methods: ['GET'])]
    public function delete(Request $request, string $identifier): Response
    {
        $this->denyUnlessSameHotel($identifier);

        $request->query->remove('confirmed');

        return parent::delete($request, $identifier);
    }

    #[Route('/{identifier}/delete', name: '_delete_confirm', methods: ['POST'])]
    public function deleteConfirmed(Request $request, string $identifier): Response
    {
        $this->denyUnlessSameHotel($identifier);

        if (!$this->isCsrfTokenValid('aureum_crud_delete_' . $identifier, (string)$request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $request->query->set('confirmed', '1');

        return parent::delete($request, $identifier);
    }

    protected function denyUnlessSameHotel(string $identifier): void
    {
        $entity = $this->repository->find($identifier);
        if ($entity === null) {
            throw $this->createNotFoundException();
        }

        $hotel = $this->getAureumService()->getHotel();

        if (!$entity instanceof HotelOwnedInterface
            || $hotel === null
            || $entity->getHotel()?->getId() !== $hotel->getId()
        ) {
            throw $this->createNotFoundException();
        }
    }
}
