<?php

namespace Alchemy\Tests\Phrasea\Utilities\String;

use Alchemy\Phrasea\Utilities\StringHelper;

/**
 * @group functional
 * @group legacy
 */
class CrLfNormalizerTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideStrings
     * @covers Alchemy\Phrasea\Utilities\StringHelper::crlfNormalize
     */
    public function testCrLfNormalize($string, $expected)
    {
        $result = StringHelper::crlfNormalize($string);

        $this->assertEquals($expected, $result);
    }

    public function provideStrings()
    {
        return [
            ['ABC\rDEF', 'ABC\nDEF'],
            ['ABC\nDEF', 'ABC\nDEF'],
            ['ABC\r\nDEF', 'ABC\nDEF'],
            ['ABC\n\rDEF', 'ABC\n\nDEF'],
        ];
    }
}
