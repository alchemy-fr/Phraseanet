<?php

require_once __DIR__ . '/PhraseanetPHPUnitAbstract.class.inc';

class unicodeTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var unicode
     */
    protected $object;

    /**
     * @covers \unicode::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new unicode();
    }

    /**
     * @covers \unicode::remove_diacritics
     */
    public function testRemove_diacritics()
    {
        $this->assertEquals('Elephant', $this->object->remove_diacritics('Eléphant'));
        $this->assertEquals('&e"\'(-e_ca)=$*u:;,?./§%µ£°0987654321œ3~#{[|^`@]}e³²÷×¿', $this->object->remove_diacritics('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿'));
        $this->assertEquals('PeTARDS', $this->object->remove_diacritics('PéTARDS'));
    }

    /**
     * @covers \unicode::remove_nonazAZ09
     */
    public function testRemove_nonazAZ09()
    {
        $this->assertEquals('Elephant', $this->object->remove_nonazAZ09('Eléphant'));
        $this->assertEquals('e-e_cau09876543213e', $this->object->remove_nonazAZ09('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿', true, true));
        $this->assertEquals('eecau09876543213e', $this->object->remove_nonazAZ09('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿', false, false));
        $this->assertEquals('ee_cau09876543213e', $this->object->remove_nonazAZ09('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿', true, false));
        $this->assertEquals('e-ecau09876543213e', $this->object->remove_nonazAZ09('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿', false, true));
        $this->assertEquals('PeTARDS', $this->object->remove_nonazAZ09('PéTARDS'));
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

        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/testfiles/MacOSRoman.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/testfiles/ISOLatin1.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/testfiles/ISOLatin2.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/testfiles/Latin5.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/testfiles/UTF-8.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/testfiles/WindowsLatin1.txt')));
        $this->assertEquals($reference, $this->object->toUTF8(file_get_contents(__DIR__ . '/testfiles/WindowsLatin2.txt')));
    }

    /**
     * @covers \unicode::parseDate
     */
    public function testparseDate()
    {
        $date = '2012';

        $this->assertEquals('2012/00/00 00:00:00', $this->object->parseDate('2012'));
        $this->assertEquals('2012/01/00 00:00:00', $this->object->parseDate('2012-01'));
        $this->assertEquals('2012/03/15 00:00:00', $this->object->parseDate('2012-03-15'));
        $this->assertEquals('2012/03/15 12:00:00', $this->object->parseDate('2012-03-15 12'));
        $this->assertEquals('2012/03/15 12:11:00', $this->object->parseDate('2012-03-15 12:11'));
        $this->assertEquals('2012/03/15 12:12:12', $this->object->parseDate('2012-03-15 12-12-12'));
    }
}

