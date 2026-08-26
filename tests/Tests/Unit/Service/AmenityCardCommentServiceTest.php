<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service;

use Citadel\Aureum\Core\Entity\AmenityCard;
use Citadel\Aureum\Core\Entity\AmenityCardComment;
use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Repository\AmenityCardCommentRepository;
use Citadel\Aureum\Core\Service\AmenityCardCommentService;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Tests\Unit\EntityIdTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AmenityCardCommentServiceTest extends TestCase
{
    use EntityIdTrait;

    public function testAddingACommentStampsTheAuthorAndTrimsTheBody(): void
    {
        $employee = $this->employee(1);
        $card = new AmenityCard();

        $comment = $this->service($employee)->add($card, "  Guest out until 6pm, deliver later.  \n");

        self::assertNotNull($comment);
        self::assertSame('Guest out until 6pm, deliver later.', $comment->getBody());
        self::assertSame($employee, $comment->getAuthor());
        self::assertCount(1, $card->getComments());
    }

    public function testABlankCommentIsIgnored(): void
    {
        $card = new AmenityCard();

        self::assertNull($this->service($this->employee(1))->add($card, "   \n  "));
        self::assertCount(0, $card->getComments());
    }

    public function testTheAuthorCanModifyTheirOwnComment(): void
    {
        $employee = $this->employee(1);
        $comment = $this->comment($employee);

        self::assertTrue($this->service($employee)->canModify($comment));
    }

    public function testAManagerCanModifyAnyComment(): void
    {
        $comment = $this->comment($this->employee(1));

        self::assertTrue($this->service($this->employee(2), manager: true)->canModify($comment));
    }

    public function testOtherEmployeesCannotModify(): void
    {
        $comment = $this->comment($this->employee(1));
        $service = $this->service($this->employee(2));

        self::assertFalse($service->canModify($comment));

        $this->expectException(AccessDeniedException::class);
        $service->delete($comment);
    }

    public function testTheAuthorCanUpdateAndTheEditIsMarked(): void
    {
        $employee = $this->employee(1);
        $comment = $this->comment($employee);
        $comment->setBody('original');

        $this->service($employee)->update($comment, 'corrected note');

        self::assertSame('corrected note', $comment->getBody());
        self::assertTrue($comment->isEdited());
    }

    public function testOthersCannotUpdate(): void
    {
        $comment = $this->comment($this->employee(1));

        $this->expectException(AccessDeniedException::class);
        $this->service($this->employee(2))->update($comment, 'hijacked');
    }

    private function service(Employee $employee, bool $manager = false): AmenityCardCommentService
    {
        $aureumService = $this->createStub(AureumService::class);
        $aureumService->method('getEmployee')->willReturn($employee);

        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn($manager);

        return new AmenityCardCommentService(
            $aureumService,
            $security,
            $this->createStub(AmenityCardCommentRepository::class),
        );
    }

    private function employee(int $id): Employee
    {
        $employee = new Employee();
        $this->withId($employee, $id);

        return $employee;
    }

    private function comment(Employee $author): AmenityCardComment
    {
        $comment = new AmenityCardComment();
        $comment->setAuthor($author);
        $this->withId($comment, 1);

        return $comment;
    }
}
