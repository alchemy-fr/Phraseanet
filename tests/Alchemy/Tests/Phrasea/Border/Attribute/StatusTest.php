<?php

namespace Alchemy\Tests\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Border\Attribute\Status;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;

class StatusTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Status
     */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Attribute
     * @covers Alchemy\Phrasea\Border\Attribute\Status::__construct
     * @dataProvider getValidStatuses
     */
    public function testConstructor($status, $binaryString)
    {
        $attr = new Status(self::$DI['app'], $status);
        $this->assertEquals($binaryString, $attr->getValue());
    }

    public function getValidStatuses()
    {
        return array(
          array(123, '1111011'),
          array('123', '1111011'),
          array('0b1111011', '1111011'),
          array('1111011', '1111011'),
          array('0x7b', '1111011'),
          array('7b', '1111011'),
        );
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Status::__construct
     * @dataProvider getInvalidStatuses
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConstruction($status)
    {
        new Status(self::$DI['app'], $status);
    }

    public function getInvalidStatuses()
    {
        return array(
          array('0b00z2'),
          array('0x00g2'),
          array('g2'),
        );
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Status::getName
     */
    public function testGetName()
    {
        $status = new Status(self::$DI['app'], 123);
        $this->assertEquals(AttributeInterface::NAME_STATUS, $status->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Status::getValue
     */
    public function testGetValue()
    {
        $status = new Status(self::$DI['app'], 123);
        $this->assertEquals('1111011', $status->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Status::asString
     */
    public function testAsString()
    {
        $status = new Status(self::$DI['app'], 123);
        $this->assertEquals('1111011', $status->asString());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Status::loadFromString
     */
    public function testLoadFromString()
    {
        $status = new Status(self::$DI['app'], 12345);

        $this->assertEquals($status, Status::loadFromString(self::$DI['app'], $status->asString()));
    }
}
