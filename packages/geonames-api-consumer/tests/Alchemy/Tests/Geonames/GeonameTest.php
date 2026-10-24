<?php

namespace Alchemy\Tests\Geonames;

use Alchemy\Geonames\Geoname;

class GeonameTest extends \PHPUnit\Framework\TestCase
{
    public function testGetter()
    {
        $geoname = new Geoname(array(
            'key'  => 'value',
            'key2' => array('value2'),
        ));

        $this->assertSame('value', $geoname->get('key'));
        $this->assertSame(array('value2'), $geoname->get('key2'));
        $this->assertSame(null, $geoname->get('key3'));
    }

    public function testHas()
    {
        $geoname = new Geoname(array(
            'key'  => 'value',
            'key2' => array('value2'),
        ));

        $this->assertTrue($geoname->has('key'));
        $this->assertTrue($geoname->has('key2'));
        $this->assertFalse($geoname->has('key3'));
    }

    public function testToString()
    {
        $data = array(
            'key'  => 'value',
            'key2' => array('value2'),
        );
        $geoname = new Geoname($data);

        $this->assertSame(json_encode($data), (string) $geoname);
    }
}
