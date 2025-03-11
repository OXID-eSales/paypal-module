<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Helper;

use OxidSolutionCatalysts\PayPal\Helper\Str2Float;
use PHPUnit\Framework\TestCase;

class Str2FloatTest extends TestCase
{
    /**
     * @dataProvider dataSource
     */
    public function testStr2Float(string $input, ?float $expected): void
    {
        $converter = new Str2Float();
        $result = $converter->autoParse($input);
        $this->assertSame($expected, $result);
    }

    public function dataSource(): array
    {
        return [
            [
                '123.45', 123.45
            ],
            [
                '123,456', 123.456
            ],
            [
                '1,23.456', null
            ],
            [
                '1112,23.456', null
            ],
            [
                '11.12,23.456', null
            ],
            [
                '1.123.234,56', 1123234.56
            ],
            [
                '1,123,234.56', 1123234.56
            ],
            [
                '1,123.23', 1123.23
            ],
            [
                '1.123,23', 1123.23
            ],
            [
                '1,123,230', 1123230.0
            ],
            [
                '1.123.230', 1123230.0
            ],
            [
                '1.123.230,456', 1123230.456
            ],
            [
                '1.123.230.456', 1123230456.0
            ],
        ];
    }
}
