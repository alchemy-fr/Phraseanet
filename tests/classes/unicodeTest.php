<?php

/**
 * @group functional
 * @group legacy
 */
class unicodeTest extends \PhraseanetTestCase
{
    /**
     * @var unicode
     */
    protected $object;

    public function setUp()
    {
        $this->object = new unicode();
    }

    public function tearDown()
    {
        // no-op
    }

    /**
     * @covers \unicode::convert
     */
    public function testConvert()
    {
        $testStr = 'ÉLÉPHANT à rôtir';
        $this->assertEquals('éléphant à rôtir', $this->object->convert($testStr, unicode::CONVERT_TO_LC));
        $this->assertEquals('ELEPHANT a rotir', $this->object->convert($testStr, unicode::CONVERT_TO_ND));
        $this->assertEquals('elephant a rotir', $this->object->convert($testStr, unicode::CONVERT_TO_LCND));
    }

    /**
     * @covers \unicode::convert
     * @expectedException        \Exception_InvalidArgument
     */
    public function testConvertError()
    {
        $this->object->convert('ÉLÉPHANT à rôtir', 'UNDEFINED_CONSTANT');
    }

    /**
     * @covers \unicode::remove_diacritics
     */
    public function testRemove_diacritics()
    {
        $testStr = '&é"\'(-èÉ_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿';
        $this->assertEquals('&e"\'(-eE_ca)=$*u:;,?./§%μ£°0987654321Œ3~#{[|^`@]}e32÷×¿',$this->object->remove_diacritics($testStr));
    }

    /**
     * @covers \unicode::remove_nonazAZ09
     */
    public function testRemove_nonazAZ09()
    {
        $testStr = 'É&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿';
        $this->assertEquals('Ee-e_cau.09876543213e32', $this->object->remove_nonazAZ09($testStr, true, true, true));
        $this->assertEquals('Ee-e_cau09876543213e32' , $this->object->remove_nonazAZ09($testStr, true, true, false));
        $this->assertEquals('Eee_cau.09876543213e32' , $this->object->remove_nonazAZ09($testStr, true, false, true));
        $this->assertEquals('Ee-ecau.09876543213e32' , $this->object->remove_nonazAZ09($testStr, false, true, true));
        $this->assertEquals('Eeecau09876543213e32'   , $this->object->remove_nonazAZ09($testStr, false, false, false));
    }

    /**
     * @covers \unicode::remove_first_digits
     */
    public function testRemove_first_digits()
    {
        $this->assertEquals('', $this->object->remove_first_digits('123456789'));
        $this->assertEquals('abcdeé', $this->object->remove_first_digits('12345abcdeé'));
        $this->assertEquals('abcdeé0987', $this->object->remove_first_digits('abcdeé0987'));
        $this->assertEquals('a2b5cdeé', $this->object->remove_first_digits('4a2b5cdeé'));
    }

    /**
     * @covers \unicode::substituteCtrlCharacters
     */
    public function testSubstituteCtrlCharacters()
    {
        $string = 'Hello' . chr(30) . 'World !';
        $this->assertEquals('Hello+World !', $this->object->substituteCtrlCharacters($string, '+'));

        $string = 'Hello' . chr(9) . 'World !';
        $this->assertEquals($string, $this->object->substituteCtrlCharacters($string, '+'));
    }

    /**
     * @covers \unicode::toUTF8
     */
    public function testToUTF8()
    {
        $reference = 'Un éléphant ça trompe énormément !';

        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/../files/MacOSRoman.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/../files/ISOLatin1.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/../files/ISOLatin2.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/../files/Latin5.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/../files/UTF-8.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/../files/WindowsLatin1.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/../files/WindowsLatin2.txt')));
    }

    /**
     * @covers \unicode::parseDate
     */
    public function testparseDate()
    {
        $this->assertEquals('2012/00/00 00:00:00', $this->object->parseDate('2012'));
        $this->assertEquals('2012/01/00 00:00:00', $this->object->parseDate('2012-01'));
        $this->assertEquals('2012/03/15 00:00:00', $this->object->parseDate('2012-03-15'));
        $this->assertEquals('2012/03/15 12:00:00', $this->object->parseDate('2012-03-15 12'));
        $this->assertEquals('2012/03/15 12:11:00', $this->object->parseDate('2012-03-15 12:11'));
        $this->assertEquals('2012/03/15 12:12:12', $this->object->parseDate('2012-03-15 12-12-12'));
    }
}
