<?php

declare(strict_types=1);

namespace Citadel\Aureum\Admin\Controller;

use Citadel\Aureum\Admin\Form\GoogleApiKeyType;
use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/integrations', name: 'integrations')]
#[IsGranted('aureum.admin.settings.manage')]
class IntegrationsController extends AbstractController
{
    public function __construct(
        private readonly GooglePlacesKeyManager $keyManager,
    ) {
    }

    #[Route('', name: '')]
    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(GoogleApiKeyType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->keyManager->setKey($form->get('apiKey')->getData());
            $this->addFlash('success', 'Google Places API key saved.');

            return $this->redirectToRoute('aureum_admin_integrations');
        }

        return $this->render('@CitadelAureum/admin/integrations/integrations.html.twig', [
            'form' => $form->createView(),
            'hasKey' => $this->keyManager->hasKey(),
            'keySetAt' => $this->keyManager->getKeySetAt(),
        ]);
    }

    #[Route('/remove-key', name: '_remove_key', methods: ['POST'])]
    public function removeKey(Request $request): Response
    {
        $token = (string)$request->request->get('_token');
        if (!$this->isCsrfTokenValid('aureum_remove_google_key', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->keyManager->removeKey();
        $this->addFlash('success', 'Google Places API key removed.');

        return $this->redirectToRoute('aureum_admin_integrations');
    }
}
