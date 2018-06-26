<?php

namespace Alchemy\Tests\Phrasea\Utilities\String;

use Alchemy\Phrasea\Utilities\StringHelper;

/**
 * @group unit
 */
class StringHelperTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideStringsForCamelize
     * @covers Alchemy\Phrasea\Utilities\StringHelper::camelize
     */
    public function testCamelize($string, $separator, $expected, $pascalize)
    {
        $result = StringHelper::camelize($string, $separator, $pascalize);

        $this->assertEquals($expected, $result);
    }

    public function provideStringsForCamelize()
    {
        return [
            ['string-test', '-', 'stringTest', false],
            ['string test', ' ', 'stringTest', false],
            ['string_test', '_', 'stringTest', false],
            ['string#test', '#', 'StringTest', true],
        ];
    }



    /**
     * @dataProvider provideStringsForCrLfNormalize
     * @covers Alchemy\Phrasea\Utilities\StringHelper::crlfNormalize
     */
    public function testCrLfNormalize($string, $expected)
    {
        $result = StringHelper::crlfNormalize($string);

        $this->assertEquals($expected, $result);
    }

    public function provideStringsForCrLfNormalize()
    {
        return [
            ["ABC\rDEF", "ABC\nDEF"],
            ["ABC\nDEF", "ABC\nDEF"],
            ["ABC\r\nDEF", "ABC\nDEF"],
            ["ABC\n\rDEF", "ABC\n\nDEF"],
        ];
    }

    /**
     * @dataProvider provideStringsForSqlQuote
     * @covers Alchemy\Phrasea\Utilities\StringHelper::SqlQuote
     */
    public function testSqlQuote($string, $mode, $expected)
    {
        $result = StringHelper::SqlQuote($string, $mode);

        $this->assertEquals($expected, $result);
    }

    public function provideStringsForSqlQuote()
    {
        return [
            ["azerty",  StringHelper::SQL_VALUE, "'azerty'"],
            ["aze'rty", StringHelper::SQL_VALUE, "'aze''rty'"],
            ["aze`rty", StringHelper::SQL_VALUE, "'aze`rty'"],
            ["azerty",  StringHelper::SQL_IDENTIFIER, "`azerty`"],
            ["aze'rty", StringHelper::SQL_IDENTIFIER, "`aze'rty`"],
            ["aze`rty", StringHelper::SQL_IDENTIFIER, "`aze``rty`"],
        ];
    }
}
