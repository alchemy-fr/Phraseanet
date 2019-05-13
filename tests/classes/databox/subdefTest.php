<?php

use Alchemy\Phrasea\Media\Subdef\OptionType\OptionType;
use Alchemy\Phrasea\Media\Subdef;
use Alchemy\Phrasea\Media\Type;
use MediaAlchemyst\Specification;
use Symfony\Component\Translation\TranslatorInterface;

class databox_subdefTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    protected function setUp()
    {
        $this->translator = $this->getTranslatorMock();
    }

    public function testImage()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="preview" name="preview_api" downloadable="true">
                    <path>/home/datas/noweb/db_alch_phrasea/subdefs/</path>
                    <meta>yes</meta>
                    <mediatype>image</mediatype>
                    <label lang="fr">Prévisualisation</label>
                    <label lang="en">Preview</label>
                    <size>1000</size>
                    <dpi>150</dpi>
                    <strip>no</strip>
                    <quality>75</quality>
                </subdef>';

        $type = new Type\Image();
        $object = new databox_subdef($type, simplexml_load_string($xml), $this->translator);

        $this->assertEquals(databox_subdef::CLASS_PREVIEW, $object->get_class());
        $this->assertEquals('/home/datas/noweb/db_alch_phrasea/subdefs/', $object->get_path());
        $this->assertInstanceOf(Subdef\Subdef::class, $object->getSubdefType());
        $this->assertEquals($type, $object->getSubdefGroup());

        $labels = $object->get_labels();
        $this->assertTrue(is_array($labels));
        $this->assertArrayHasKey('fr', $labels);
        $this->assertEquals('Prévisualisation', $labels['fr']);
        $this->assertArrayHasKey('en', $labels);
        $this->assertEquals('Preview', $labels['en']);

        $this->assertTrue($object->isDownloadable());
        $this->assertTrue(is_array($object->getAvailableSubdefTypes()));
        $this->assertTrue(count($object->getAvailableSubdefTypes()) > 0);

        foreach ($object->getAvailableSubdefTypes() as $type) {
            $this->assertInstanceOf(Subdef\Subdef::class, $type);
        }

        $this->assertTrue($object->isMetadataUpdateRequired());

        $this->assertEquals('preview_api', $object->get_name());
        $this->assertInstanceOf(Specification\Image::class, $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf(OptionType::class, $option);
        }
    }

    public function testVideo()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="video_api" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
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
        $object = new databox_subdef($type, simplexml_load_string($xml), $this->translator);

        $this->assertEquals(databox_subdef::CLASS_THUMBNAIL, $object->get_class());
        $this->assertEquals('/home/datas/noweb/db_alch_phrasea/video/', $object->get_path());
        $this->assertInstanceOf(Subdef\Subdef::class, $object->getSubdefType());
        $this->assertEquals($type, $object->getSubdefGroup());

        $labels = $object->get_labels();
        $this->assertTrue(is_array($labels));
        $this->assertEquals(0, count($labels));

        $this->assertFalse($object->isDownloadable());
        $this->assertTrue(is_array($object->getAvailableSubdefTypes()));
        $this->assertTrue(count($object->getAvailableSubdefTypes()) > 0);

        foreach ($object->getAvailableSubdefTypes() as $type) {
            $this->assertInstanceOf(Subdef\Subdef::class, $type);
        }

        $this->assertFalse($object->isMetadataUpdateRequired());

        $this->assertEquals('video_api', $object->get_name());
        $this->assertInstanceOf(Specification\Video::class, $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf(OptionType::class, $option);
        }
    }

    public function testGif()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="gifou" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
                    <meta>no</meta>
                    <mediatype>gif</mediatype>
                    <size>200</size>
                    <strip>yes</strip>
                    <delay>100</delay>
                </subdef>';

        $type = new \Alchemy\Phrasea\Media\Type\Video();
        $object = new databox_subdef($type, simplexml_load_string($xml), $this->translator);

        $this->assertInstanceOf(Specification\Animation::class, $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf(OptionType::class, $option);
        }
    }

    public function testAudio()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="gifou" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
                    <mediatype>audio</mediatype>
                </subdef>';

        $type = new \Alchemy\Phrasea\Media\Type\Audio();
        $object = new databox_subdef($type, simplexml_load_string($xml), $this->translator);

        $this->assertInstanceOf(Specification\Audio::class, $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf(OptionType::class, $option);
        }
    }

    public function testFlexPaper()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="gifou" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
                    <mediatype>flexpaper</mediatype>
                </subdef>';

        $type = new \Alchemy\Phrasea\Media\Type\Flash();
        $object = new databox_subdef($type, simplexml_load_string($xml), $this->translator);

        $this->assertInstanceOf(Specification\Flash::class, $object->getSpecs());

        $options = $object->getOptions();
        $this->assertTrue(is_array($options));

        foreach ($options as $option) {
            $this->assertInstanceOf(OptionType::class, $option);
        }
    }

    /**
     * @dataProvider getVariouasTypeAndSubdefs
     */
    public function testGetAvailableSubdefTypes($object)
    {
        foreach ($object->getAvailableSubdefTypes() as $type) {
            $this->assertInstanceOf(Subdef\Subdef::class, $type);
        }
    }

    public function getVariouasTypeAndSubdefs()
    {
        $xmlImage = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <subdef class="thumbnail" name="gifou" downloadable="false">
                    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
                    <mediatype>image</mediatype>
                </subdef>';

        $translator = $this->getTranslatorMock();

        return [
            [new databox_subdef(new Type\Audio(), simplexml_load_string($xmlImage), $translator)],
            [new databox_subdef(new Type\Document(), simplexml_load_string($xmlImage), $translator)],
            [new databox_subdef(new Type\Video(), simplexml_load_string($xmlImage), $translator)],
        ];
    }

    /**
     * @param bool $expected
     * @param null|string $configValue
     * @dataProvider providesOrderableStatuses
     */
    public function testOrderableStatus($expected, $configValue, $message)
    {
        $xmlTemplate = <<<'EOF'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<subdef class="thumbnail" name="gifou" downloadable="false"%s>
    <path>/home/datas/noweb/db_alch_phrasea/video/</path>
    <mediatype>image</mediatype>
</subdef>
EOF;

        if (null !== $configValue) {
            $configValue = ' orderable="' . $configValue . '"';
        }

        $xml = sprintf($xmlTemplate, $configValue ?: '');

        $sut = new databox_subdef(new Type\Image(), simplexml_load_string($xml), $this->translator);

        $this->assertSame($expected, $sut->isOrderable(), $message);
    }

    public function providesOrderableStatuses()
    {
        return [
            [true, null, 'No Orderable Status set should defaults to true'],
            [false, 'false', 'Orderable should be false'],
            [true, 'true', 'Orderable should be true'],
        ];
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    private function getTranslatorMock()
    {
        return $this->getMock(TranslatorInterface::class);
    }
}
