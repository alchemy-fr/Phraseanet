<?php

namespace Alchemy\Tests\Phrasea\Twig;

use Alchemy\Phrasea\Twig\BytesConverter;

class BytesConverterTest extends \PHPUnit_Framework_TestCase
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
        return array(
            array('', 123456789012345, '112.28 TB'),
            array('Auto', 123456789012345, '112.28 TB'),
            array('Human', 123456789012345, '112.28 TB'),
            array('KB', 123456789012345, '120563270519.87 KB'),
            array('MB', 123456789012345, '117737568.87 MB'),
            array('GB', 123456789012345, '114978.09 GB'),
            array('TB', 123456789012345, '112.28 TB'),
            array('PB', 123456789012345, '0.11 PB'),
        );
    }
}
