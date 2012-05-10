<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class FilenameTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Filename
     */
    protected $object;
    protected static $need_records = 1;

    public function setUp()
    {
        parent::setUp();
        $this->object = new Filename;
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Filename::check
     */
    public function testCheck()
    {
        $response = $this->object->check(self::$core['EM'], new \Alchemy\Phrasea\Border\File(__DIR__ . '/../../../../testfiles/test001.CR2', self::$collection));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Filename::check
     */
    public function testCheckNoFile()
    {
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getOriginalName'), array(__DIR__ . '/../../../../testfiles/test001.CR2', self::$collection));

        $mock
            ->expects($this->once())
            ->method('getOriginalName')
            ->will($this->returnValue(\random::generatePassword(32)))
        ;

        $response = $this->object->check(self::$core['EM'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Filename::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->object->getMessage());
    }
}
