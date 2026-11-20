<?php

namespace Alchemy\Tests\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Border\Attribute\Status;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class StatusTest extends \PhraseanetTestCase
{
    /**
     * @var Status
     */
    protected $object;

    #[\PHPUnit\Framework\Attributes\DataProvider('getValidStatuses')]
    public function testConstructor($status, $binaryString)
    {
        $attr = new Status(self::$DI['app'], $status);
        $this->assertEquals($binaryString, $attr->getValue());
    }

    public static function getValidStatuses()
    {
        return [
          [123, '1111011'],
          ['123', '1111011'],
          ['0b1111011', '1111011'],
          ['1111011', '1111011'],
          ['0x7b', '01111011'],
          ['7b', '01111011'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getInvalidStatuses')]
    public function testInvalidConstruction($status)
    {
        $this->expectException(\InvalidArgumentException::class);
        new Status(self::$DI['app'], $status);
    }

    public static function getInvalidStatuses()
    {
        return [
          ['0b00z2'],
          ['0x00g2'],
          ['g2'],
        ];
    }

    public function testGetName()
    {
        $status = new Status(self::$DI['app'], 123);
        $this->assertEquals(AttributeInterface::NAME_STATUS, $status->getName());
    }

    public function testGetValue()
    {
        $status = new Status(self::$DI['app'], 123);
        $this->assertEquals('1111011', $status->getValue());
    }

    public function testAsString()
    {
        $status = new Status(self::$DI['app'], 123);
        $this->assertEquals('1111011', $status->asString());
    }

    public function testLoadFromString()
    {
        $status = new Status(self::$DI['app'], 12345);

        $this->assertEquals($status, Status::loadFromString(self::$DI['app'], $status->asString()));
    }
}
