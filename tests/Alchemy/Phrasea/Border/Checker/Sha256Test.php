<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class Sha256Test extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Sha256
     */
    protected $object;
    protected static $need_records = 1;

    public function setUp()
    {
        parent::setUp();
        $this->object = new Sha256;
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Sha256::check
     */
    public function testCheck()
    {
        $response = $this->object->check(self::$core['EM'], new \Alchemy\Phrasea\Border\File(__DIR__ . '/../../../../testfiles/test001.CR2', self::$collection));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Sha256::check
     */
    public function testCheckNoFile()
    {
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getSha256'), array(__DIR__ . '/../../../../testfiles/test001.CR2', self::$collection));

        $mock
            ->expects($this->once())
            ->method('getSha256')
            ->will($this->returnValue(\random::generatePassword(3)))
        ;

        $response = $this->object->check(self::$core['EM'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Sha256::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->object->getMessage());
    }
}
