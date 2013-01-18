<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Audio;
use Alchemy\Phrasea\Media\Subdef\Subdef;

class AudioTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Audio
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Audio();
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Audio::getType
     */
    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_AUDIO, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Audio::getDescription
     */
    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Audio::getMediaAlchemystSpec
     */
    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Audio', $this->object->getMediaAlchemystSpec());
    }
}
