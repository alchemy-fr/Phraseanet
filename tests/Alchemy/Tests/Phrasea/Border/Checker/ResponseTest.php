<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\Checker\CheckerInterface;
use Alchemy\Phrasea\Border\Checker\Response;
use Alchemy\Tests\Tools\TranslatorMockTrait;

class ResponseTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mock;
    /** @var Response */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Response::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->mock = $this->getMock(CheckerInterface::class);
        $this->object = new Response(true, $this->mock);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Response::__destruct
     */
    public function tearDown()
    {
        $this->mock = $this->object = null;
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\CheckerInterface
     * @covers Alchemy\Phrasea\Border\Checker\Response::isOk
     */
    public function testIsOk()
    {
        $this->assertTrue($this->object->isOk());

        $this->object = new Response(false, $this->mock);
        $this->assertFalse($this->object->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\CheckerInterface
     * @covers Alchemy\Phrasea\Border\Checker\Response::getChecker
     */
    public function testGetCheck()
    {
        $this->assertSame($this->mock, $this->object->getChecker());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Response::getMessage
     */
    public function testGetMessage()
    {
        $this->mock
            ->expects($this->any())
            ->method('getMessage')
            ->will($this->returnValue('Hello World'));

        $this->object = new Response(true, $this->mock);

        $this->assertEquals('Hello World', $this->object->getMessage($this->createTranslatorMock()));
    }
}
