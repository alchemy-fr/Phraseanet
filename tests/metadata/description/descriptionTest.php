<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class metadataDescriptionTest extends PhraseanetPHPUnitAbstract
{
    protected $metadatas = array();

    public function setUp()
    {
        parent::setUp();
        $this->metadatas = databox::get_available_metadatas();
    }

    public function testMetadatas()
    {
        foreach ($this->metadatas as $metadata) {
            $this->assertInstanceOf('metadata_Interface', $metadata);
            $this->assertInstanceOf('metadata_Abstract', $metadata);

            $this->assertTrue(is_bool($metadata::is_multi()));
            $this->assertEquals($metadata::MULTI, $metadata::is_multi());

            $this->assertTrue(is_bool($metadata::is_deprecated()));
            $this->assertEquals($metadata::DEPRECATED, $metadata::is_deprecated());

            $this->assertTrue(is_bool($metadata::is_readonly()));
            $this->assertEquals($metadata::READONLY, $metadata::is_readonly());

            $this->assertTrue(is_bool($metadata:: is_mandatory()));
            $this->assertEquals($metadata::MANDATORY, $metadata::is_mandatory());

            $this->assertTrue(is_array($metadata::available_values()));
            foreach ($metadata::available_values() as $value) {
                $this->assertTrue(is_string($value));
            }

            $this->assertTrue(is_string($metadata::get_tagname()));
            $this->assertEquals($metadata::TAGNAME, $metadata::get_tagname());

            $this->assertTrue(is_string($metadata::get_type()));
            $this->assertEquals($metadata::TYPE, $metadata::get_type());

            $this->assertTrue(is_string($metadata::get_namespace()));
            $this->assertEquals($metadata::NAME_SPACE, $metadata::get_namespace());

            $this->assertEquals($metadata::SOURCE, $metadata::get_source());
            if ($metadata instanceof metadata_description_nosource) {
                $this->assertNull($metadata::get_source());
            } else {
                $this->assertTrue(is_string($metadata::get_source()));
                $this->assertTrue(strpos($metadata::get_source(), '/rdf:RDF/rdf:Description/') === 0, get_class($metadata));
            }

            $this->assertEquals($metadata::MIN_LENGTH, $metadata::minlength());
            if ( ! is_null($metadata::minlength())) {
                $this->assertTrue(is_int($metadata::minlength()));
                $this->assertTrue($metadata::minlength() >= 0);
            }

            $this->assertEquals($metadata::MAX_LENGTH, $metadata::maxlength());
            if ( ! is_null($metadata::maxlength())) {
                $this->assertTrue(is_int($metadata::maxlength()));
                $this->assertTrue($metadata::maxlength() >= 0);
            }
        }
    }
}
