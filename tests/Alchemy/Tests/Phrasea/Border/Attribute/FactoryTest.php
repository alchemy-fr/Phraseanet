<?php

namespace Alchemy\Tests\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Border\Attribute\Factory;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Border\Attribute\MetaField;
use Alchemy\Phrasea\Metadata\TagFactory;
use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Driver\Metadata\Metadata;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FactoryTest extends \PhraseanetTestCase
{

    public function testGetFileAttributeMetadata()
    {
        $tag = TagFactory::getFromRDFTagname('IPTC:UniqueDocumentID');
        $value = new Mono('Unique');

        $metadata = new Metadata($tag, $value);

        $attribute = Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_METADATA, (new \Alchemy\Phrasea\Border\Attribute\Metadata($metadata))->asString());

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Metadata', $attribute);
    }

    public function testGetFileAttributeMetadataFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_METADATA, null);
    }

    public function testGetFileAttributeStory()
    {
        $attribute = Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_STORY, self::$DI['record_story_1']->get_serialize_key());

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Story', $attribute);
    }

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

        $metafield = new MetaField($databox_field, ['value']);

        $attribute = Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_METAFIELD, $metafield->asString());

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\MetaField', $attribute);
    }

    public function testGetFileAttributeStatus()
    {
        $attribute = Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_STATUS, '000100');

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Status', $attribute);
    }

    public function testGetFileAttributeStoryFailsRecord()
    {
        $this->expectException(\InvalidArgumentException::class);
        Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_STORY, self::$DI['record_1']->get_serialize_key());
    }

    public function testGetFileAttributeStoryFails()
    {
        $this->expectException(\InvalidArgumentException::class);

        Factory::getFileAttribute(self::$DI['app'], AttributeInterface::NAME_STORY, self::$DI['collection']->get_databox()->get_sbas_id() . '_0');
    }

    public function testGetFileAttributeFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        Factory::getFileAttribute(self::$DI['app'], 'nothing', 'nothong');
    }
}
