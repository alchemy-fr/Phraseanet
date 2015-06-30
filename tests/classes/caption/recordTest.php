<?php

use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Symfony\Component\Yaml\Yaml;

/**
 * @group functional
 * @group legacy
 */
class caption_recordTest extends \PhraseanetTestCase
{
    /**
     * @var caption_record
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new caption_record(self::$DI['app'], self::$DI['record_1'], self::$DI['record_1']->get_databox());
    }

    /**
     * @covers \caption_record::serializeXML
     */
    public function testSerializeXML()
    {

        foreach (self::$DI['record_1']->get_databox()->get_meta_structure() as $databox_field) {
            $n = $databox_field->is_multi() ? 3 : 1;

            for ($i = 0; $i < $n; $i ++) {
                \caption_Field_Value::create(self::$DI['app'], $databox_field, self::$DI['record_1'], self::$DI['app']['random.low']->generateString(8));
            }
        }

        $xml = self::$DI['app']['serializer.caption']->serialize($this->object, CaptionSerializer::SERIALIZE_XML);

        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);

        foreach (self::$DI['record_1']->get_caption()->get_fields() as $field) {
            if ($field->get_databox_field()->is_multi()) {
                $tagname = $field->get_name();
                $retrieved = [];
                foreach ($sxe->description->$tagname as $value) {
                    $retrieved[] = (string) $value;
                }

                $values = $field->get_values();
                $this->assertEquals(count($values), count($retrieved));
                foreach ($values as $val) {
                    $this->assertTrue(in_array($val->getValue(), $retrieved));
                }
            } else {
                $tagname = $field->get_name();
                $data = $field->get_values();
                $value = array_pop($data);
                $this->assertEquals($value->getValue(), (string) $sxe->description->$tagname);
            }
        }
    }

    public function testSerializeJSON()
    {
        foreach (self::$DI['record_1']->get_databox()->get_meta_structure() as $databox_field) {
            $n = $databox_field->is_multi() ? 3 : 1;

            for ($i = 0; $i < $n; $i ++) {
                \caption_Field_Value::create(self::$DI['app'], $databox_field, self::$DI['record_1'], self::$DI['app']['random.low']->generateString(8));
            }
        }

        $json = json_decode(self::$DI['app']['serializer.caption']->serialize($this->object, CaptionSerializer::SERIALIZE_JSON), true);

        foreach (self::$DI['record_1']->get_caption()->get_fields() as $field) {
            if ($field->get_databox_field()->is_multi()) {
                $tagname = $field->get_name();
                $retrieved = [];
                foreach ($json["record"]["description"][$tagname] as $value) {
                    $retrieved[] = $value;
                }

                $values = $field->get_values();
                $this->assertEquals(count($values), count($retrieved));
                foreach ($values as $val) {
                    $this->assertTrue(in_array($val->getValue(), $retrieved));
                }
            } else {
                $tagname = $field->get_name();
                $data = $field->get_values();
                $value = array_pop($data);
                $this->assertEquals($value->getValue(), $json["record"]["description"][$tagname]);
            }
        }
    }

    /**
     * @covers \caption_record::serializeYAML
     */
    public function testSerializeYAML()
    {
        foreach (self::$DI['record_1']->get_databox()->get_meta_structure() as $databox_field) {
            $n = $databox_field->is_multi() ? 3 : 1;

            for ($i = 0; $i < $n; $i ++) {
                \caption_Field_Value::create(self::$DI['app'], $databox_field, self::$DI['record_1'], self::$DI['app']['random.low']->generateString(8));
            }
        }

        $parser = new Yaml();
        $yaml = $parser->parse(self::$DI['app']['serializer.caption']->serialize($this->object, CaptionSerializer::SERIALIZE_YAML));

        foreach (self::$DI['record_1']->get_caption()->get_fields() as $field) {
            if ($field->get_databox_field()->is_multi()) {
                $tagname = $field->get_name();
                $retrieved = [];
                foreach ($yaml["record"]["description"][$tagname] as $value) {
                    $retrieved[] = (string) $value;
                }

                $values = $field->get_values();
                $this->assertEquals(count($values), count($retrieved));
                foreach ($values as $val) {
                    $this->assertTrue(in_array($val->getValue(), $retrieved));
                }
            } else {
                $tagname = $field->get_name();
                $data = $field->get_values();
                $value = array_pop($data);
                $this->assertEquals($value->getValue(), (string) $yaml["record"]["description"][$tagname]);
            }
        }
    }

    /**
     * @covers \caption_record::get_fields
     * @todo Implement testGet_fields().
     */
    public function testGet_fields()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \caption_record::get_field
     * @todo Implement testGet_field().
     */
    public function testGet_field()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \caption_record::get_dc_field
     * @todo Implement testGet_dc_field().
     */
    public function testGet_dc_field()
    {
        $field = null;

        foreach (self::$DI['app']->getDataboxes() as $databox) {
            foreach ($databox->get_meta_structure() as $meta) {
                $meta->set_dces_element(new databox_Field_DCES_Contributor());
                $field = $meta;
                $set = true;
                break;
            }
            break;
        }

        if (!$field) {
            $this->markTestSkipped('Unable to set a DC field');
        }

        $captionField = self::$DI['record_1']->get_caption()->get_field($field->get_name());

        if (!$captionField) {
            self::$DI['record_1']->set_metadatas([
                [
                    'meta_id'        => null,
                    'meta_struct_id' => $field->get_id(),
                    'value'          => ['HELLO MO !'],
                ]
            ]);
            $value = 'HELLO MO !';
        } else {
            $value = $captionField->get_serialized_values();
        }

        $this->assertEquals($value, self::$DI['record_1']->get_caption()->get_dc_field(databox_Field_DCESAbstract::Contributor)->get_serialized_values());
    }

    /**
     * @covers \caption_record::get_highlight_fields
     * @todo Implement testGet_highlight_fields().
     */
    public function testGet_highlight_fields()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \caption_record::get_cache_key
     * @todo Implement testGet_cache_key().
     */
    public function testGet_cache_key()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \caption_record::get_data_from_cache
     * @todo Implement testGet_data_from_cache().
     */
    public function testGet_data_from_cache()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \caption_record::set_data_to_cache
     * @todo Implement testSet_data_to_cache().
     */
    public function testSet_data_to_cache()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \caption_record::delete_data_from_cache
     * @todo Implement testDelete_data_from_cache().
     */
    public function testDelete_data_from_cache()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
