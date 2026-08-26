<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Entity;

use Citadel\Aureum\Core\Entity\Enum\SopStatus;
use Citadel\Aureum\Core\Entity\Sop;
use PHPUnit\Framework\TestCase;

class SopTest extends TestCase
{
    public function testBodyTextIsDerivedFromTheHtmlBody(): void
    {
        $sop = new Sop();
        $sop->setBody('<h2>Purpose</h2><p>Greet the guest &amp; escort them.</p><ul><li>Step   one</li></ul>');

        self::assertSame('Purpose Greet the guest & escort them. Step one', $sop->getBodyText());
    }

    public function testBodyTextOfAnEmptyBodyIsEmpty(): void
    {
        $sop = new Sop();
        $sop->setBody('');

        self::assertSame('', $sop->getBodyText());
    }

    public function testNewSopsStartAsDraftVersionOne(): void
    {
        $sop = new Sop();

        self::assertSame(SopStatus::DRAFT, $sop->getStatus());
        self::assertSame(1, $sop->getVersion());
        self::assertNull($sop->getPublishedAt());
    }

    public function testBumpVersionIncrements(): void
    {
        $sop = new Sop();
        $sop->bumpVersion();
        $sop->bumpVersion();

        self::assertSame(3, $sop->getVersion());
    }

    public function testPublishSetsPublishedAtOnceOnly(): void
    {
        $sop = new Sop();
        $sop->publish();
        $first = $sop->getPublishedAt();

        self::assertSame(SopStatus::PUBLISHED, $sop->getStatus());
        self::assertNotNull($first);

        $sop->setStatus(SopStatus::ARCHIVED);
        $sop->publish();

        self::assertSame($first, $sop->getPublishedAt());
        self::assertSame(SopStatus::PUBLISHED, $sop->getStatus());
    }
}
