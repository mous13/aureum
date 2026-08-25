<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller;

use Citadel\Aureum\Core\Security\AureumVoter;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/docs', name: 'docs_')]
#[IsGranted('ROLE_USER')]
class DocsController extends AbstractController
{
    private const PAGES = [
        'welcome' => [
            'title' => 'Welcome to Aureum',
            'summary' => 'What Aureum is, how the navigation works, and where to start.',
            'section' => 'Getting started',
        ],
        'managing-staff' => [
            'title' => 'Creating & onboarding staff',
            'summary' => 'Add a new employee, assign roles, and walk them through their first login.',
            'section' => 'Getting started',
            'permission' => AureumVoter::EMPLOYEE_MANAGE,
        ],
        'bookings' => [
            'title' => 'Bookings',
            'summary' => 'Track guest bookings from arrival to checkout.',
            'section' => 'Modules',
            'permission' => 'aureum.module.bookings.view',
        ],
        'rooms' => [
            'title' => 'Rooms directory',
            'summary' => 'Browse rooms by floor, leave room comments, and manage floors and room types.',
            'section' => 'Modules',
            'permission' => 'aureum.module.rooms.view',
        ],
        'restaurants' => [
            'title' => 'Restaurants',
            'summary' => 'The dining directory, with cuisines and staff recommendations.',
            'section' => 'Modules',
            'permission' => 'aureum.module.restaurants.view',
        ],
        'events' => [
            'title' => 'Events',
            'summary' => 'The hotel events calendar.',
            'section' => 'Modules',
            'permission' => 'aureum.module.events.view',
        ],
        'packages' => [
            'title' => 'Packages',
            'summary' => 'Log guest deliveries and hand them over.',
            'section' => 'Modules',
            'permission' => 'aureum.module.packages.view',
        ],
        'fines' => [
            'title' => 'Fines',
            'summary' => 'Record chargeable incidents and their outcomes.',
            'section' => 'Modules',
            'permission' => 'aureum.module.fines.view',
        ],
        'lost-property' => [
            'title' => 'Lost property',
            'summary' => 'Register found items and reunite them with their owners.',
            'section' => 'Modules',
            'permission' => 'aureum.module.lost_property.view',
        ],
        'roles-and-access' => [
            'title' => 'Roles & access',
            'summary' => 'How roles, module permissions, and the modules screen fit together.',
            'section' => 'Administration',
            'permission' => AureumVoter::RBAC_MANAGE,
        ],
        'data-and-retention' => [
            'title' => 'Data & retention',
            'summary' => 'Retention policies, the access log, and how guest data is anonymised.',
            'section' => 'Administration',
            'permission' => AureumVoter::RBAC_MANAGE,
        ],
    ];

    public function __construct(
        private readonly AureumService $aureumService,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $visible = $this->getVisiblePages();
        if ($visible === []) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('aureum_docs_page', ['slug' => array_key_first($visible)]);
    }

    #[Route('/{slug}', name: 'page')]
    public function page(string $slug): Response
    {
        $visible = $this->getVisiblePages();
        if (!isset($visible[$slug])) {
            throw $this->createNotFoundException();
        }

        $slugs = array_keys($visible);
        $position = array_search($slug, $slugs, true);
        $previous = $position > 0 ? $slugs[$position - 1] : null;
        $next = $position < count($slugs) - 1 ? $slugs[$position + 1] : null;

        $chapters = [];
        foreach ($visible as $pageSlug => $page) {
            $chapters[$page['section']][$pageSlug] = $page;
        }

        return $this->render("@CitadelAureum/core/docs/pages/{$slug}.html.twig", [
            'slug' => $slug,
            'page' => $visible[$slug],
            'chapters' => $chapters,
            'previous' => $previous !== null ? ['slug' => $previous, ...$visible[$previous]] : null,
            'next' => $next !== null ? ['slug' => $next, ...$visible[$next]] : null,
        ]);
    }

    /**
     * @return array<string, array{title: string, summary: string, section: string, permission?: string}>
     */
    private function getVisiblePages(): array
    {
        if (!$this->aureumService->isEmployee()) {
            return [];
        }

        return array_filter(
            self::PAGES,
            fn (array $page): bool => !isset($page['permission']) || $this->isGranted($page['permission']),
        );
    }
}
