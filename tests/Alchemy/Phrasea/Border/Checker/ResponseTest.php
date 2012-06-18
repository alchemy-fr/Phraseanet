<?php

namespace Alchemy\Phrasea\Border\Checker;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    protected $mock;
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Response::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\Checker\\CheckerInterface', array('getMessage', 'check', 'isApplicable'));
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
            ->staticExpects($this->any())
            ->method('getMessage')
            ->will($this->returnValue('Hello World'));

        $this->object = new Response(true, $this->mock);

        $this->assertEquals('Hello World', $this->object->getMessage());
    }
}
