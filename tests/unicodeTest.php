<?php

require_once __DIR__ . '/PhraseanetPHPUnitAbstract.class.inc';

class unicodeTest extends PhraseanetPHPUnitAbstract
{

    /**
     * @var unicode
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new unicode();
    }

    public function testRemove_diacritics()
    {
        $this->assertEquals('Elephant', $this->object->remove_diacritics('Eléphant'));
        $this->assertEquals('&e"\'(-e_ca)=$*u:;,?./§%µ£°0987654321œ3~#{[|^`@]}e³²÷×¿', $this->object->remove_diacritics('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿'));
        $this->assertEquals('PeTARDS', $this->object->remove_diacritics('PéTARDS'));
    }

    public function testRemove_nonazAZ09()
    {
        $this->assertEquals('Elephant', $this->object->remove_nonazAZ09('Eléphant'));
        $this->assertEquals('e-e_cau09876543213e', $this->object->remove_nonazAZ09('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿', true, true));
        $this->assertEquals('eecau09876543213e', $this->object->remove_nonazAZ09('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿', false, false));
        $this->assertEquals('ee_cau09876543213e', $this->object->remove_nonazAZ09('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿', true, false));
        $this->assertEquals('e-ecau09876543213e', $this->object->remove_nonazAZ09('&é"\'(-è_çà)=$*ù:;,?./§%µ£°0987654321Œ3~#{[|^`@]}ê³²÷×¿', false, true));
        $this->assertEquals('PeTARDS', $this->object->remove_nonazAZ09('PéTARDS'));
    }

    public function testRemove_first_digits()
    {
        $this->assertEquals('', $this->object->remove_first_digits('123456789'));
        $this->assertEquals('abcdeé', $this->object->remove_first_digits('12345abcdeé'));
        $this->assertEquals('abcdeé0987', $this->object->remove_first_digits('abcdeé0987'));
        $this->assertEquals('a2b5cdeé', $this->object->remove_first_digits('4a2b5cdeé'));
    }

}

