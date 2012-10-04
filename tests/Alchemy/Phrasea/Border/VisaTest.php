<?php

namespace Alchemy\Phrasea\Border;

class VisaTest extends \PhraseanetPHPUnitAbstract
{

    /**
     * @covers Alchemy\Phrasea\Border\Visa::__construct
     * @covers Alchemy\Phrasea\Border\Visa::__destruct
     */
    public function testVisa()
    {
        $visa = new Visa();
        $visa = null;
    }

    /**
     * @covers Alchemy\Phrasea\Border\Visa::addResponse
     * @covers Alchemy\Phrasea\Border\Visa::getResponses
     */
    public function testResponses()
    {
        $visa = new Visa();

        $this->assertEquals(array(), $visa->getResponses());

        $response = new Checker\Response(true, new Checker\Filename(self::$DI['app']));
        $visa->addResponse($response);
        $response2 = new Checker\Response(false, new Checker\Filename(self::$DI['app']));
        $visa->addResponse($response2);

        $this->assertSame(array($response, $response2), $visa->getResponses());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Visa::isValid
     */
    public function testIsValid()
    {
        $visa = new Visa();

        $this->assertTrue($visa->isValid());

        $response = new Checker\Response(true, new Checker\Filename(self::$DI['app']));
        $visa->addResponse($response);

        $this->assertTrue($visa->isValid());

        $response2 = new Checker\Response(false, new Checker\Filename(self::$DI['app']));
        $visa->addResponse($response2);

        $this->assertFalse($visa->isValid());
    }
}
