<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Security;

use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
class AureumVoter extends Voter
{
    public const MODULE_PREFIX = 'aureum.module.';
    public const RBAC_MANAGE = 'aureum.rbac.manage';

    public function __construct(
        private readonly AureumService $aureumService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::RBAC_MANAGE || str_starts_with($attribute, self::MODULE_PREFIX);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $employee = $this->aureumService->getEmployee();
        if ($employee === null) {
            return false;
        }

        if ($attribute === self::RBAC_MANAGE) {
            return $employee->isHotelAdmin();
        }

        [$moduleValue, $action] = $this->parse($attribute);

        $module = Module::tryFrom($moduleValue);
        if ($module === null || !in_array($action, $module->permissions(), true)) {
            return false;
        }

        if (!$employee->getHotel()->isModuleEnabled($module)) {
            return false;
        }

        if ($employee->isHotelAdmin()) {
            return true;
        }

        $actions = $module->permissions();
        $requiredIndex = array_search($action, $actions, true);
        if ($requiredIndex === false) {
            return false;
        }

        foreach ($employee->getHotelRoles() as $role) {
            foreach (array_slice($actions, $requiredIndex) as $grantingAction) {
                if ($role->hasPermission("{$moduleValue}.{$grantingAction}")) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{0: string, 1: string} [module value, action]
     */
    private function parse(string $attribute): array
    {
        $rest = substr($attribute, strlen(self::MODULE_PREFIX));
        $pos = strrpos($rest, '.');
        if ($pos === false) {
            return [$rest, ''];
        }

        return [substr($rest, 0, $pos), substr($rest, $pos + 1)];
    }
}
