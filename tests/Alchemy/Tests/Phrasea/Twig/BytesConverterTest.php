<?php

namespace Alchemy\Tests\Phrasea\Twig;

use Alchemy\Phrasea\Twig\BytesConverter;

class BytesConverterTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert($suffix, $bytes, $expected)
    {
        $converter = new BytesConverter();
        $this->assertEquals($converter->bytes2Filter($suffix, $bytes), $expected);
    }

    public function convertDataProvider()
    {
        return [
            ['', 123456789012345, '112.28 TB'],
            ['Auto', 123456789012345, '112.28 TB'],
            ['Human', 123456789012345, '112.28 TB'],
            ['KB', 123456789012345, '120563270519.87 KB'],
            ['MB', 123456789012345, '117737568.87 MB'],
            ['GB', 123456789012345, '114978.09 GB'],
            ['TB', 123456789012345, '112.28 TB'],
            ['PB', 123456789012345, '0.11 PB'],
        ];
    }
}
