<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class caption_recordTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var caption_record
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new caption_record(self::$application, static::$records['record_1'], static::$records['record_1']->get_databox());
    }

    /**
     * @covers \caption_record::serializeXML
     */
    public function testSerializeXML()
    {

        foreach (static::$records['record_1']->get_databox()->get_meta_structure() as $databox_field) {
            $n = $databox_field->is_multi() ? 3 : 1;

            for ($i = 0; $i < $n; $i ++ ) {
                \caption_Field_Value::create(self::$application, $databox_field, static::$records['record_1'], \random::generatePassword());
            }
        }

        $xml = $this->object->serialize(\caption_record::SERIALIZE_XML);

        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);

        foreach (static::$records['record_1']->get_caption()->get_fields() as $field) {
            if ($field->get_databox_field()->is_multi()) {
                $tagname = $field->get_name();
                $retrieved = array();
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
                $value = array_pop($field->get_values());
                $this->assertEquals($value->getValue(), (string) $sxe->description->$tagname);
            }
        }
    }

    /**
     * @covers \caption_record::serializeYAML
     */
    public function testSerializeYAML()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
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
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
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
