<?php

namespace Alchemy\Tests\Phrasea\Utilities\String;

use Alchemy\Phrasea\Utilities\StringHelper;

#[\PHPUnit\Framework\Attributes\Group('unit')]
class StringHelperTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideStringsForCamelize')]
    public function testCamelize($string, $separator, $expected, $pascalize)
    {
        $result = StringHelper::camelize($string, $separator, $pascalize);

        $this->assertEquals($expected, $result);
    }

    public static function provideStringsForCamelize()
    {
        return [
            ['string-test', '-', 'stringTest', false],
            ['string test', ' ', 'stringTest', false],
            ['string_test', '_', 'stringTest', false],
            ['string#test', '#', 'StringTest', true],
        ];
    }



    #[\PHPUnit\Framework\Attributes\DataProvider('provideStringsForCrLfNormalize')]
    public function testCrLfNormalize($string, $expected)
    {
        $result = StringHelper::crlfNormalize($string);

        $this->assertEquals($expected, $result);
    }

    public static function provideStringsForCrLfNormalize()
    {
        return [
            ["ABC\rDEF", "ABC\nDEF"],
            ["ABC\nDEF", "ABC\nDEF"],
            ["ABC\r\nDEF", "ABC\nDEF"],
            ["ABC\n\rDEF", "ABC\n\nDEF"],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideStringsForSqlQuote')]
    public function testSqlQuote($string, $mode, $expected)
    {
        $result = StringHelper::SqlQuote($string, $mode);

        $this->assertEquals($expected, $result);
    }

    public static function provideStringsForSqlQuote()
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
