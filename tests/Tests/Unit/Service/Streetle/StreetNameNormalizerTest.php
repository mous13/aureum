<?php

declare(strict_types=1);

namespace Citadel\Aureum\Tests\Unit\Service\Streetle;

use Citadel\Aureum\Core\Service\Streetle\StreetNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StreetNameNormalizerTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function equivalentNames(): iterable
    {
        yield 'case and spacing' => ['Deansgate', '  deansgate '];
        yield 'street abbreviation' => ['Peter Street', 'peter st'];
        yield 'saint abbreviation' => ["St Ann's Square", "Saint Anns Square"];
        yield 'square abbreviation' => ["St Ann's Square", 'st anns sq'];
        yield 'road abbreviation' => ['Oxford Road', 'Oxford Rd.'];
        yield 'lane abbreviation' => ['Back Turner Lane', 'back turner ln'];
        yield 'leading article' => ['The Avenue', 'avenue'];
        yield 'ampersand' => ['Bridge & Wharf', 'bridge and wharf'];
        yield 'curly apostrophe' => ['King’s Street', 'kings street'];
        yield 'hyphen' => ['Cross-Street', 'cross street'];
        yield 'direction' => ['Great Ducie Street North', 'gt ducie st n'];
    }

    #[DataProvider('equivalentNames')]
    public function testEquivalentNamesNormaliseTheSame(string $official, string $guess): void
    {
        $normalizer = new StreetNameNormalizer();

        self::assertSame($normalizer->normalize($official), $normalizer->normalize($guess));
    }

    public function testDifferentStreetsStayDifferent(): void
    {
        $normalizer = new StreetNameNormalizer();

        self::assertNotSame($normalizer->normalize('King Street'), $normalizer->normalize('King Street West'));
        self::assertNotSame($normalizer->normalize('Oxford Road'), $normalizer->normalize('Oxford Street'));
    }

    public function testPunctuationOnlyNormalisesToEmpty(): void
    {
        self::assertSame('', (new StreetNameNormalizer())->normalize(" .'- "));
    }
}
