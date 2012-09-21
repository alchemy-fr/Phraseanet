<?php

require_once __DIR__ . '/PhraseanetPHPUnitAbstract.class.inc';

class geonamesTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var geonames
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new geonames(self::$application);
    }

    public function testName_from_id()
    {
        $result = $this->object->name_from_id(2989317);
        $this->assertEquals("Orléans, France", $result);
    }

    public function testGet_country()
    {
        $orleans = $this->object->find_city('orléans, france');
        $this->assertTrue(is_array($orleans));
        $this->assertTrue(count($orleans) === 1);
        $orleans = array_pop($orleans);

        $found = $this->object->get_country($orleans['geoname_id']);

        $this->assertEquals($found, $orleans['country']);
        $this->assertEquals($found, 'France');
    }

    public function testGet_country_code()
    {
        $this->assertEquals('FR', $this->object->get_country_code(2989317));
        $this->assertEquals('', $this->object->get_country_code(298945135163153317));
        $this->assertEquals('', $this->object->get_country_code('29894513516315331dsfsd7'));
        $this->assertEquals('', $this->object->get_country_code('dsfsd'));
    }

    public function testFind_city()
    {
        $orleans = $this->object->find_city('orléa');
        $this->assertTrue(is_array($orleans));
        foreach ($orleans as $potential) {
            $this->assertArrayHasKey('region', $potential);
            $this->assertArrayHasKey('title_highlighted', $potential);
            $this->assertArrayHasKey('country', $potential);
            $this->assertArrayHasKey('title', $potential);
            $this->assertArrayHasKey('country_highlighted', $potential);
            $this->assertArrayHasKey('geoname_id', $potential);
            $this->assertTrue(is_int($potential['geoname_id']));
            $this->assertTrue(is_string($potential['country_highlighted']));
            $this->assertTrue(is_string($potential['title']));
            $this->assertTrue(is_string($potential['country']));
            $this->assertTrue(is_string($potential['title_highlighted']));
            $this->assertTrue(is_string($potential['region']));
        }
    }

    public function testFind_geoname_from_ip()
    {
        $result = $this->object->find_geoname_from_ip('80.12.81.18');
        $this->assertArrayHasKey('city', $result);
        $this->assertArrayHasKey('country_code', $result);
        $this->assertArrayHasKey('country', $result);
        $this->assertArrayHasKey('fips', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertEquals("Paris", $result['city']);
        $this->assertEquals("FR", $result['country_code']);
        $this->assertEquals("France", $result['country']);
    }
}
