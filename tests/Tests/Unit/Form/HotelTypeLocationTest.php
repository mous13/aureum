<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Form;

use Citadel\Aureum\Admin\Form\HotelType;
use Citadel\Aureum\Core\Entity\Hotel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Validation;

class HotelTypeLocationTest extends TestCase
{
    /** @return iterable<string, array{?float, ?float, int}> */
    public static function locations(): iterable
    {
        yield 'both set' => [53.4791, -2.2453, 0];
        yield 'both empty' => [null, null, 0];
        yield 'latitude only' => [53.4791, null, 1];
        yield 'longitude only' => [null, -2.2453, 1];
    }

    #[DataProvider('locations')]
    public function testLatitudeAndLongitudeGoTogether(?float $latitude, ?float $longitude, int $violations): void
    {
        $hotel = new Hotel();
        $hotel->setLatitude($latitude);
        $hotel->setLongitude($longitude);

        $result = Validation::createValidator()->validate($hotel, new Callback(HotelType::validateLocation(...)));

        self::assertCount($violations, $result);
    }

    public function testTheLocationIsLongitudeThenLatitude(): void
    {
        $hotel = new Hotel();
        self::assertNull($hotel->getLocation());

        $hotel->setLatitude(53.4791);
        self::assertNull($hotel->getLocation());

        $hotel->setLongitude(-2.2453);
        self::assertSame([-2.2453, 53.4791], $hotel->getLocation());
    }
}
