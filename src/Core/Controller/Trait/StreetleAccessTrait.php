<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Controller\Trait;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Entity\StreetleSession;

trait StreetleAccessTrait
{
    private function employee(): Employee
    {
        return $this->aureumService->getEmployee() ?? throw $this->createAccessDeniedException();
    }

    private function findSession(int $id): StreetleSession
    {
        return $this->sessionRepository->findOwned($id, $this->employee()) ?? throw $this->createNotFoundException();
    }

    private function denyUnlessValidCsrf(string $token): void
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
