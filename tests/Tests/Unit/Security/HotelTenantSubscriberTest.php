<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Security;

use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\Transfer;
use Citadel\Aureum\Core\Security\HotelTenantSubscriber;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Tests\Unit\EntityIdTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HotelTenantSubscriberTest extends TestCase
{
    use EntityIdTrait;

    private const OWN_HOTEL = 1;
    private const OTHER_HOTEL = 2;

    public function testRejectsRecordBelongingToAnotherHotel(): void
    {
        $subscriber = $this->subscriber(self::OWN_HOTEL);
        $fine = $this->fineFor(self::OTHER_HOTEL);

        $this->expectException(NotFoundHttpException::class);

        $subscriber($this->event([$fine], 'aureum_fines_edit'));
    }

    public function testAllowsRecordBelongingToTheCallersOwnHotel(): void
    {
        $subscriber = $this->subscriber(self::OWN_HOTEL);
        $fine = $this->fineFor(self::OWN_HOTEL);

        $subscriber($this->event([$fine], 'aureum_fines_edit'));

        $this->addToAssertionCount(1);
    }

    /**
     * A route that resolves several entities is only safe if every one of them
     * is checked, not just the first.
     */
    public function testRejectsWhenAnyOneArgumentBelongsToAnotherHotel(): void
    {
        $subscriber = $this->subscriber(self::OWN_HOTEL);

        $this->expectException(NotFoundHttpException::class);

        $subscriber($this->event(
            [$this->fineFor(self::OWN_HOTEL), $this->transferFor(self::OTHER_HOTEL)],
            'aureum_fines_edit',
        ));
    }

    public function testRejectsWhenTheCallerIsNotAnEmployee(): void
    {
        $subscriber = $this->subscriber(null);

        $this->expectException(NotFoundHttpException::class);

        $subscriber($this->event([$this->fineFor(self::OWN_HOTEL)], 'aureum_fines_edit'));
    }

    /**
     * The platform admin surface is gated on ROLE_ADMIN and legitimately spans
     * every hotel, so it must not be scoped to the caller's own hotel.
     */
    public function testLeavesTheAdminSurfaceAlone(): void
    {
        $subscriber = $this->subscriber(self::OWN_HOTEL);

        $subscriber($this->event([$this->fineFor(self::OTHER_HOTEL)], 'aureum_admin_employees_edit'));

        $this->addToAssertionCount(1);
    }

    public function testIgnoresArgumentsThatAreNotHotelOwned(): void
    {
        $subscriber = $this->subscriber(null);

        $subscriber($this->event(['a string', 42, new \stdClass()], 'aureum_fines'));

        $this->addToAssertionCount(1);
    }

    #[DataProvider('unownedRecordProvider')]
    public function testRejectsRecordWithNoHotelAtAll(callable $factory): void
    {
        $subscriber = $this->subscriber(self::OWN_HOTEL);

        $this->expectException(NotFoundHttpException::class);

        $subscriber($this->event([$factory()], 'aureum_fines_edit'));
    }

    public static function unownedRecordProvider(): array
    {
        return [
            'fine with no hotel set' => [static fn (): Fine => new Fine()],
            'transfer with no hotel set' => [static fn (): Transfer => new Transfer()],
        ];
    }

    private function subscriber(?int $employeeHotelId): HotelTenantSubscriber
    {
        $service = $this->createMock(AureumService::class);

        if ($employeeHotelId === null) {
            $service->method('getHotel')->willReturn(null);
        } else {
            $service->method('getHotel')->willReturn($this->hotel($employeeHotelId));
        }

        return new HotelTenantSubscriber($service);
    }

    private function hotel(int $id): Hotel
    {
        /** @var Hotel $hotel */
        $hotel = $this->withId(new Hotel(), $id);

        return $hotel;
    }

    private function fineFor(int $hotelId): Fine
    {
        $fine = new Fine();
        $fine->setHotel($this->hotel($hotelId));

        return $fine;
    }

    private function transferFor(int $hotelId): Transfer
    {
        $transfer = new Transfer();
        $transfer->setHotel($this->hotel($hotelId));

        return $transfer;
    }

    /**
     * @param array<mixed> $arguments
     */
    private function event(array $arguments, string $route): ControllerArgumentsEvent
    {
        $request = new Request();
        $request->attributes->set('_route', $route);

        return new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            static fn (): null => null,
            $arguments,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
