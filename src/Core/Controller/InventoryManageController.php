<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('aureum.module.inventory.manage')]
class InventoryManageController extends AbstractController
{
    #[Route('/inventory/manage', name: 'inventory_manage', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('aureum_inventory_manage_inventories');
    }
}
