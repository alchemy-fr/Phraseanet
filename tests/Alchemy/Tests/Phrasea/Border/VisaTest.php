<?php

namespace Alchemy\Tests\Phrasea\Border;

use Alchemy\Phrasea\Border\Visa;
use Alchemy\Phrasea\Border\Checker\Filename;
use Alchemy\Phrasea\Border\Checker\Response;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class VisaTest extends \PhraseanetTestCase
{

    public function testVisa()
    {
        $visa = new Visa();
        $visa = null;
    }

    public function testResponses()
    {
        $visa = new Visa();

        $this->assertEquals([], $visa->getResponses());

        $response = new Response(true, new Filename(self::$DI['app']));
        $visa->addResponse($response);
        $response2 = new Response(false, new Filename(self::$DI['app']));
        $visa->addResponse($response2);

        $this->assertSame([$response, $response2], $visa->getResponses());
    }

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
