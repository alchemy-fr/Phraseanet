<?php

namespace Alchemy\Tests\Phrasea\Utilities\String;

use Alchemy\Phrasea\Utilities\StringHelper;

/**
 * @group functional
 * @group legacy
 */
class CamelizerTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideStrings
     * @covers Alchemy\Phrasea\Utilities\StringHelper::camelize
     */
    public function testCamelize($string, $separator, $expected, $pascalize)
    {
        $result = StringHelper::camelize($string, $separator, $pascalize);

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
