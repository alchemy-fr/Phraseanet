<?php

class databox_subdefTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers databox_subdef::__construct
     * @covers databox_subdef::get_class
     * @covers databox_subdef::get_name
     * @covers databox_subdef::meta_writeable
     * @covers databox_subdef::getAvailableSubdefTypes
     * @covers databox_subdef::is_downloadable
     * @covers databox_subdef::get_labels
     * @covers databox_subdef::getSubdefGroup
     * @covers databox_subdef::getSubdefType
     * @covers databox_subdef::get_baseurl
     * @covers databox_subdef::get_path
     * @covers databox_subdef::getSpecs
     * @covers databox_subdef::getOptions
     * @covers databox_subdef::buildImageSubdef
     */
    public function testImage()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="preview" name="preview_api" downloadable="true">
                    <path>/home/datas/noweb/db_alch_phrasea/subdefs/</path>
                    <baseurl/>
                    <meta>yes</meta>
                    <mediatype>image</mediatype>
                    <label lang="fr">Prévisualisation</label>
                    <label lang="en">Preview</label>
                    <size>1000</size>
                    <dpi>150</dpi>
                    <strip>no</strip>
                    <quality>75</quality>
                </subdef>';

        $type = new \Alchemy\Phrasea\Media\Type\Image();
        $object = new databox_subdef($type, simplexml_load_string($xml));

        $this->assertEquals(databox_subdef::CLASS_PREVIEW, $object->get_class());
        $this->assertEquals('/home/datas/noweb/db_alch_phrasea/subdefs/', $object->get_path());
        $this->assertEquals('', $object->get_baseurl());
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\Subdef\\Subdef', $object->getSubdefType());
        $this->assertEquals($type, $object->getSubdefGroup());

        $labels = $object->get_labels();
        $this->assertTrue(is_array($labels));
        $this->assertArrayHasKey('fr', $labels);
        $this->assertEquals('Prévisualisation', $labels['fr']);
        $this->assertArrayHasKey('en', $labels);
        $this->assertEquals('Preview', $labels['en']);

        $this->assertTrue($object->is_downloadable());
        $this->assertTrue(is_array($object->getAvailableSubdefTypes()));
        $this->assertTrue(count($object->getAvailableSubdefTypes()) > 0);

        foreach ($object->getAvailableSubdefTypes() as $type) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\Image', $type);
        }

        $this->assertTrue($object->meta_writeable());

        $this->assertEquals('preview_api', $object->get_name());
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Image', $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\OptionType\\OptionType', $option);
        }
    }

    /**
     * @covers databox_subdef::__construct
     * @covers databox_subdef::get_class
     * @covers databox_subdef::get_name
     * @covers databox_subdef::meta_writeable
     * @covers databox_subdef::getAvailableSubdefTypes
     * @covers databox_subdef::is_downloadable
     * @covers databox_subdef::get_labels
     * @covers databox_subdef::getSubdefGroup
     * @covers databox_subdef::getSubdefType
     * @covers databox_subdef::get_baseurl
     * @covers databox_subdef::get_path
     * @covers databox_subdef::getSpecs
     * @covers databox_subdef::getOptions
     * @covers databox_subdef::buildVideoSubdef
     */
    public function testVideo()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="video_api" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
                    <baseurl>/video/</baseurl>
                    <meta>no</meta>
                    <mediatype>video</mediatype>
                    <size>196</size>
                    <dpi>72</dpi>
                    <strip>yes</strip>
                    <quality>75</quality>
                    <fps>10</fps>
                    <threads>1</threads>
                    <bitrate>192</bitrate>
                    <acodec>libfaac</acodec>
                    <vcodec>libx264</vcodec>
                </subdef>';

        $type = new \Alchemy\Phrasea\Media\Type\Video();
        $object = new databox_subdef($type, simplexml_load_string($xml));

        $this->assertEquals(databox_subdef::CLASS_THUMBNAIL, $object->get_class());
        $this->assertEquals('/home/datas/noweb/db_alch_phrasea/video/', $object->get_path());
        $this->assertEquals('/video/', $object->get_baseurl());
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\Subdef\\Subdef', $object->getSubdefType());
        $this->assertEquals($type, $object->getSubdefGroup());

        $labels = $object->get_labels();
        $this->assertTrue(is_array($labels));
        $this->assertEquals(0, count($labels));

        $this->assertFalse($object->is_downloadable());
        $this->assertTrue(is_array($object->getAvailableSubdefTypes()));
        $this->assertTrue(count($object->getAvailableSubdefTypes()) > 0);

        foreach ($object->getAvailableSubdefTypes() as $type) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\Subdef', $type);
        }

        $this->assertFalse($object->meta_writeable());

        $this->assertEquals('video_api', $object->get_name());
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Video', $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\OptionType\\OptionType', $option);
        }
    }

    /**
     * @covers databox_subdef::__construct
     * @covers databox_subdef::getSpecs
     * @covers databox_subdef::getOptions
     * @covers databox_subdef::buildGifSubdef
     */
    public function testGif()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="gifou" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
                    <baseurl>web//db_alch_beta/subdefs/</baseurl>
                    <meta>no</meta>
                    <mediatype>gif</mediatype>
                    <size>200</size>
                    <strip>yes</strip>
                    <delay>100</delay>
                </subdef>';

        $type = new \Alchemy\Phrasea\Media\Type\Video();
        $object = new databox_subdef($type, simplexml_load_string($xml));

        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Animation', $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\OptionType\\OptionType', $option);
        }
    }

    /**
     * @covers databox_subdef::__construct
     * @covers databox_subdef::getSpecs
     * @covers databox_subdef::getOptions
     * @covers databox_subdef::buildAudioSubdef
     */
    public function testAudio()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="gifou" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
                    <mediatype>audio</mediatype>
                </subdef>';

        $type = new \Alchemy\Phrasea\Media\Type\Audio();
        $object = new databox_subdef($type, simplexml_load_string($xml));

        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Audio', $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\OptionType\\OptionType', $option);
        }
    }

    /**
     * @covers databox_subdef::__construct
     * @covers databox_subdef::getSpecs
     * @covers databox_subdef::getOptions
     * @covers databox_subdef::buildFlexPaperSubdef
     */
    public function testFlexPaper()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="gifou" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
                    <mediatype>flexpaper</mediatype>
                </subdef>';

        $type = new \Alchemy\Phrasea\Media\Type\Flash();
        $object = new databox_subdef($type, simplexml_load_string($xml));

        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Flash', $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\OptionType\\OptionType', $option);
        }
    }

    /**
     * @dataProvider getVariouasTypeAndSubdefs
     * @covers databox_subdef::getAvailableSubdefTypes
     */
    public function testGetAvailableSubdefTypes($object)
    {

        foreach ($object->getAvailableSubdefTypes() as $type) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\Subdef', $type);
        }
    }

    public function getVariouasTypeAndSubdefs()
    {

        $xmlImage = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="gifou" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
                    <mediatype>image</mediatype>
                </subdef>';

        $typeAudio = new \Alchemy\Phrasea\Media\Type\Audio();
        $typeDocument = new \Alchemy\Phrasea\Media\Type\Document();
        $typeVideo = new \Alchemy\Phrasea\Media\Type\Video();

        return array(
            array(new databox_subdef($typeAudio, simplexml_load_string($xmlImage))),
            array(new databox_subdef($typeDocument, simplexml_load_string($xmlImage))),
            array(new databox_subdef($typeVideo, simplexml_load_string($xmlImage))),
        );
    }
}
