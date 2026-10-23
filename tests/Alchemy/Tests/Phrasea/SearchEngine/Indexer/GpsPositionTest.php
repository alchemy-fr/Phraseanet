<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Indexer;

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator\GpsPosition;

/**
 * @group unit
 * @group searchengine
 * @group indexer
 */
class GpsPositionTest extends \PHPUnit\Framework\TestCase
{
    // 48.856578_N_2.351828_E

    public function testIsCompleteWithSingleSet()
    {
        $position = new GpsPosition();
        $position->set('Latitude', 48.856578);
        $this->assertFalse($position->isComplete());
    }

    public function testIsCompleteCompositeWithSingleSet()
    {
        $position = new GpsPosition();
        $position->set('Latitude', 48.856578);
        $this->assertFalse($position->isCompleteComposite());
    }

    public function testIsCompleteWithAllSet()
    {
        $position = new GpsPosition();
        $position->set('Latitude', 48.856578);
        $position->set('LatitudeRef', 'N');
        $position->set('Longitude', 2.351828);
        $position->set('LongitudeRef', 'E');
        $this->assertTrue($position->isComplete());
    }

    public function testIsCompleteCompositeWithAllSet()
    {
        $position = new GpsPosition();
        $position->set('Latitude', 48.856578);
        $position->set('Longitude', 2.351828);
        $this->assertTrue($position->isCompleteComposite());
    }

    /**
     * @dataProvider getSupportedTagNames
     */
    public function testSupportedTagNames($tag_name)
    {
        $this->assertTrue(GpsPosition::isSupportedTagName($tag_name));
    }

    public function getSupportedTagNames()
    {
        return [
            ['Longitude'],
            ['LongitudeRef'],
            ['Latitude'],
            ['LatitudeRef'],
            [GpsPosition::LONGITUDE_TAG_NAME],
            [GpsPosition::LONGITUDE_REF_TAG_NAME],
            [GpsPosition::LATITUDE_TAG_NAME],
            [GpsPosition::LATITUDE_REF_TAG_NAME]
        ];
    }

    /**
     * @dataProvider getUnsupportedTagNames
     */
    public function testUnsupportedTagNames($tag_name)
    {
        $this->assertFalse(GpsPosition::isSupportedTagName($tag_name));
    }

    public function getUnsupportedTagNames()
    {
        return [
            ['foo'],
            ['lOnGiTuDe']
        ];
    }

    public function testGetSignedLongitude()
    {
        $position = new GpsPosition();
        $position->set('Longitude', 2.351828);
        $this->assertEquals(2.351828, $position->getSignedLongitude());

        $position = new GpsPosition();
        $position->set('LongitudeRef', 'E');
        $this->assertNull($position->getSignedLongitude());

        $position = new GpsPosition();
        $position->set('Longitude', 2.351828);
        $position->set('LongitudeRef', 'E');
        $this->assertEquals(2.351828, $position->getSignedLongitude());

        $position = new GpsPosition();
        $position->set('Longitude', 2.351828);
        $position->set('LongitudeRef', 'W');
        $this->assertEquals(-2.351828, $position->getSignedLongitude());
    }

    public function testGetCompositeLongitude()
    {
        $position = new GpsPosition();
        $position->set('Longitude', -2.351828);
        $this->assertEquals(-2.351828, $position->getCompositeLongitude());
    }

    public function testGetSignedLatitude()
    {
        $position = new GpsPosition();
        $position->set('Latitude', 48.856578);
        $this->assertEquals(48.856578, $position->getSignedLatitude());

        $position = new GpsPosition();
        $position->set('LatitudeRef', 'N');
        $this->assertNull($position->getSignedLatitude());

        $position = new GpsPosition();
        $position->set('Latitude', 48.856578);
        $position->set('LatitudeRef', 'N');
        $this->assertEquals(48.856578, $position->getSignedLatitude());

        $position = new GpsPosition();
        $position->set('Latitude', 48.856578);
        $position->set('LatitudeRef', 'S');
        $this->assertEquals(-48.856578, $position->getSignedLatitude());
    }

    public function testGetCompositeLatitude()
    {
        $position = new GpsPosition();
        $position->set('Latitude', -48.856578);
        $this->assertEquals(-48.856578, $position->getCompositeLatitude());
    }
}
