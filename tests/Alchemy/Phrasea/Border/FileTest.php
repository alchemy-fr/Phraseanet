<?php

namespace Alchemy\Phrasea\Border;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class FileTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var File
     */
    protected $object;
    protected $filename;

    /**
     * @covers Alchemy\Phrasea\Border\File::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->filename = __DIR__ . '/../../../../tmp/iphone_pic.jpg';
        copy(__DIR__ . '/../../../testfiles/iphone_pic.jpg', $this->filename);
        $this->object = new File($this->filename, self::$collection, 'originalName.txt');
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::__destruct
     */
    public function tearDown()
    {
        $this->object = null;
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getUUID
     * @covers Alchemy\Phrasea\Border\File::ensureUUID
     */
    public function testGetUuid()
    {
        $this->assertEquals('4d006e01-bc38-4aac-9a5b-2c90ffe3a8a2', $this->object->getUUID());
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getUUID
     * @covers Alchemy\Phrasea\Border\File::ensureUUID
     */
    public function testNewUuid()
    {
        $file = __DIR__ . '/../../../testfiles/temporay.jpg';

        if (file_exists($file)) {
            unlink($file);
        }

        copy(__DIR__ . '/../../../testfiles/p4logo.jpg', $file);

        $borderFile = new File($file, self::$collection);
        $uuid = $borderFile->getUUID();

        $this->assertTrue(\uuid::is_valid($uuid));
        $this->assertEquals($uuid, $borderFile->getUUID());

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getSha256
     */
    public function testGetSha256()
    {
        $this->assertEquals('5f2574831efb9783f38d2f4787b50e6578c78c08c80c5fea2f05ed62103eec69', $this->object->getSha256());
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getMD5
     */
    public function testGetMD5()
    {
        $this->assertEquals('c616d7f28803fdc54a336b06e6ffe6d1', $this->object->getMD5());
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getPathfile
     */
    public function testGetPathfile()
    {
        $this->assertEquals(realpath(__DIR__ . '/../../../../tmp/iphone_pic.jpg'), $this->object->getPathfile());
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getMedia
     */
    public function testGetMedia()
    {
        $this->assertInstanceof('\\MediaVorus\\Media\\Image', $this->object->getMedia());
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getCollection
     */
    public function testGetCollection()
    {
        $this->assertSame(self::$collection, $this->object->getCollection());
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getOriginalName
     */
    public function testOriginalName()
    {
        $this->assertSame('originalName.txt', $this->object->getOriginalName());
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getOriginalName
     */
    public function testOriginalNameAuto()
    {
        $object = new File($this->filename, self::$collection);
        $this->assertSame('iphone_pic.jpg', $object->getOriginalName());
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::getAttributes
     */
    public function testGetAttributes()
    {
        $this->assertSame(array(), $this->object->getAttributes());
    }

    /**
     * @covers Alchemy\Phrasea\Border\File::addAttribute
     */
    public function testAddAttribute()
    {
        $tag = new \PHPExiftool\Driver\Tag\MXF\ObjectName();
        $value = new \PHPExiftool\Driver\Value\Mono('Object name');

        $metadata1 = new \PHPExiftool\Driver\Metadata\Metadata($tag, $value);
        $attribute1 = new Attribute\Metadata($metadata1);

        $this->object->addAttribute($attribute1);
        $this->assertSame(array($attribute1), $this->object->getAttributes());

        $tag = new \PHPExiftool\Driver\Tag\IPTC\Keywords();
        $value = new \PHPExiftool\Driver\Value\Multi(array('Later', 'Alligator'));

        $metadata2 = new \PHPExiftool\Driver\Metadata\Metadata($tag, $value);
        $attribute2 = new Attribute\Metadata($metadata2);

        $this->object->addAttribute($attribute2);
        $this->assertSame(array($attribute1, $attribute2), $this->object->getAttributes());
    }
}
