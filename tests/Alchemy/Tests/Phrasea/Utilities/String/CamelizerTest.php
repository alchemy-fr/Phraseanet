<?php

namespace Alchemy\Tests\Phrasea\Utilities\String;

use Alchemy\Phrasea\Utilities\String\Camelizer;

class CamelizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideStrings
     * @covers Alchemy\Phrasea\Utilities\String\Camelizer::camelize
     */
    public function testCamelize($string, $separator, $expected, $pascalize)
    {
        $camelizer = new Camelizer();
        $result = $camelizer->camelize($string, $separator, $pascalize);

        $this->assertEquals($expected, $result);
    }

    public function provideStrings()
    {
        return [
            ['string-test', '-', 'stringTest', false],
            ['string test', ' ', 'stringTest', false],
            ['string_test', '_', 'stringTest', false],
            ['string#test', '#', 'StringTest', true],
        ];
    }
}
