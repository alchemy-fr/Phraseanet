<?php

namespace Alchemy\Tests\Phrasea\Border;

use Alchemy\Phrasea\Border\Visa;
use Alchemy\Phrasea\Border\Checker\Filename;
use Alchemy\Phrasea\Border\Checker\Response;

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

        $response = new Response(true, new Filename(self::$DI['app']));
        $visa->addResponse($response);
        $response2 = new Response(false, new Filename(self::$DI['app']));
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

        $response = new Response(true, new Filename(self::$DI['app']));
        $visa->addResponse($response);

        $this->assertTrue($visa->isValid());

        $response2 = new Response(false, new Filename(self::$DI['app']));
        $visa->addResponse($response2);

        $this->assertFalse($visa->isValid());
    }
}
