<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Controller;

use Citadel\Aureum\Core\Controller\DocsController;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Security\AureumVoter;
use PHPUnit\Framework\TestCase;

class DocsControllerTest extends TestCase
{
    /**
     * @return array<string, array{title: string, summary: string, section: string, permission?: string}>
     */
    private function pages(): array
    {
        $reflection = new \ReflectionClassConstant(DocsController::class, 'PAGES');

        return $reflection->getValue();
    }

    public function testEveryPageHasATemplate(): void
    {
        foreach (array_keys($this->pages()) as $slug) {
            self::assertFileExists(
                __DIR__ . "/../../../../templates/core/docs/pages/{$slug}.html.twig",
                "Docs page '{$slug}' is registered but has no template.",
            );
        }
    }

    public function testEveryTemplateIsRegistered(): void
    {
        $slugs = array_keys($this->pages());
        foreach (glob(__DIR__ . '/../../../../templates/core/docs/pages/*.html.twig') as $template) {
            $slug = basename($template, '.html.twig');
            self::assertContains($slug, $slugs, "Template '{$slug}' exists but is not registered in DocsController.");
        }
    }

    public function testPermissionsAreKnownToTheVoter(): void
    {
        $valid = [AureumVoter::RBAC_MANAGE, AureumVoter::EMPLOYEE_MANAGE];
        foreach (Module::cases() as $module) {
            $valid[] = $module->permission('view');
            $valid[] = $module->permission('manage');
        }

        foreach ($this->pages() as $slug => $page) {
            if (isset($page['permission'])) {
                self::assertContains($page['permission'], $valid, "Docs page '{$slug}' uses an unknown permission.");
            }
        }
    }

    public function testEveryPageHasTitleSummaryAndSection(): void
    {
        foreach ($this->pages() as $slug => $page) {
            self::assertNotSame('', $page['title'] ?? '', "Docs page '{$slug}' is missing a title.");
            self::assertNotSame('', $page['summary'] ?? '', "Docs page '{$slug}' is missing a summary.");
            self::assertNotSame('', $page['section'] ?? '', "Docs page '{$slug}' is missing a section.");
        }
    }
}
