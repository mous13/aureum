<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Form;

use Citadel\Aureum\Admin\Form\HotelType;
use Citadel\Aureum\Core\Entity\Hotel;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class HotelTypeTest extends TypeTestCase
{
    public function testCoordinatesAreSavedOnTheHotel(): void
    {
        $hotel = new Hotel();
        $form = $this->factory->create(HotelType::class, $hotel);

        $form->submit(['name' => 'Aureum Hotel', 'code' => 'AUREUM', 'latitude' => '53.478123', 'longitude' => '-2.242611']);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertSame([-2.242611, 53.478123], $hotel->getLocation());
    }

    public function testHalfALocationIsRejected(): void
    {
        $form = $this->factory->create(HotelType::class, new Hotel());

        $form->submit(['name' => 'Aureum Hotel', 'code' => 'AUREUM', 'latitude' => '53.478123']);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('longitude')->getErrors());
    }

    public function testOutOfRangeCoordinatesAreRejected(): void
    {
        $form = $this->factory->create(HotelType::class, new Hotel());

        $form->submit(['name' => 'Aureum Hotel', 'code' => 'AUREUM', 'latitude' => '95', 'longitude' => '-2.24']);

        self::assertCount(1, $form->get('latitude')->getErrors());
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([new HotelType($this->createMock(Packages::class))], []),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
