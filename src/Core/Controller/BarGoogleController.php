<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Entity\Bar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bars/{id}/google', name: 'bars_google_')]
#[IsGranted('aureum.module.bars.manage')]
class BarGoogleController extends AbstractVenueGoogleController
{
    public const CSRF_TOKEN_ID = 'aureum_bar_google';

    #[Route('/search', name: 'search', methods: ['POST'])]
    public function search(Request $request, Bar $bar): Response
    {
        return $this->handleSearch($request, $bar);
    }

    #[Route('/link', name: 'link', methods: ['POST'])]
    public function link(Request $request, Bar $bar): Response
    {
        return $this->handleLink($request, $bar);
    }

    #[Route('/unlink', name: 'unlink', methods: ['POST'])]
    public function unlink(Request $request, Bar $bar): Response
    {
        return $this->handleUnlink($request, $bar);
    }

    protected function getCsrfTokenId(): string
    {
        return self::CSRF_TOKEN_ID;
    }
}
