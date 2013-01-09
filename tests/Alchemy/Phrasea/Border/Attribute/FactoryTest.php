<?php

namespace Alchemy\Phrasea\Border\Attribute;

use PHPExiftool\Driver\Tag\IPTC\UniqueDocumentID;
use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Driver\Metadata\Metadata;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class FactoryTest extends \PhraseanetPHPUnitAbstract
{

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Attribute
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     */
    public function testGetFileAttributeMetadata()
    {
        $tag = new UniqueDocumentID();
        $value = new Mono('Unique');

        $metadata = new Metadata($tag, $value);

        $attribute = Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_METADATA, serialize($metadata));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Metadata', $attribute);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     * @expectedException \InvalidArgumentException
     */
    public function testGetFileAttributeMetadataFail()
    {
        Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_METADATA, null);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     */
    public function testGetFileAttributeStory()
    {
        $attribute = Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_STORY, self::$DI['record_story_1']->get_serialize_key());

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Story', $attribute);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     */
    public function testGetFileAttributeMetaField()
    {
        $databox_field = null;
        foreach (self::$DI['collection']->get_databox()->get_meta_structure() as $df) {
            $databox_field = $df;
            break;
        }

        if (!$databox_field) {
            $this->markTestSkipped('No databox field found');
        }

        $metafield = new MetaField($databox_field, array('value'));

        $attribute = Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_METAFIELD, $metafield->asString());

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\MetaField', $attribute);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     */
    public function testGetFileAttributeStatus()
    {
        $attribute = Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_STATUS, '000100');

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Status', $attribute);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     * @expectedException \InvalidArgumentException
     */
    public function testGetFileAttributeStoryFailsRecord()
    {
        Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_STORY, self::$DI['record_1']->get_serialize_key());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     * @expectedException \InvalidArgumentException
     */
    public function testGetFileAttributeStoryFails()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;

        Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_STORY, self::$DI['collection']->get_databox()->get_sbas_id() . '_0');
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Factory::getFileAttribute
     * @expectedException \InvalidArgumentException
     */
    public function testGetFileAttributeFail()
    {
        Factory::getFileAttribute(self::$DI['app'], 'nothing', 'nothong');
    }
}
