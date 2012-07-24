<?php

require_once __DIR__ . '/PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class searchEngine_optionsTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var searchEngine_options
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new searchEngine_options();
    }

    public function testSet_locale()
    {
        $locale = 'BABA';
        $this->object->set_locale($locale);
        $this->assertEquals($locale, $this->object->get_locale());
    }

    public function testGet_locale()
    {
        $locale = null;
        $this->object->set_locale($locale);
        $this->assertEquals($locale, $this->object->get_locale());
    }

    public function testSet_sort()
    {
        $by = 'NAME';
        $sort = 'ASC';
        $this->object->set_sort($by, $sort);
        $this->assertEquals($by, $this->object->get_sortby());
        $this->assertEquals($sort, $this->object->get_sortord());
        $this->object->set_sort($by);
        $this->assertEquals($by, $this->object->get_sortby());
        $this->assertEquals(searchEngine_options::SORT_MODE_DESC, $this->object->get_sortord());
    }

    public function testGet_sortby()
    {
        $by = 'NAME';
        $sort = 'DESC';
        $this->object->set_sort($by, $sort);
        $this->assertEquals($by, $this->object->get_sortby());
        $this->assertEquals($sort, $this->object->get_sortord());
    }

    public function testGet_sortord()
    {
        $by = 'NAME';
        $sort = 'DESC';
        $this->object->set_sort($by, $sort);
        $this->assertEquals($by, $this->object->get_sortby());
        $this->assertEquals($sort, $this->object->get_sortord());
    }

    public function testSet_use_stemming()
    {
        $bool = true;
        $this->object->set_use_stemming($bool);
        $this->assertEquals($bool, $this->object->get_use_stemming());
        $bool = false;
        $this->object->set_use_stemming($bool);
        $this->assertEquals($bool, $this->object->get_use_stemming());
    }

    public function testGet_use_stemming()
    {
        $bool = true;
        $this->object->set_use_stemming($bool);
        $this->assertEquals($bool, $this->object->get_use_stemming());
        $bool = false;
        $this->object->set_use_stemming($bool);
        $this->assertEquals($bool, $this->object->get_use_stemming());
    }

    public function testSet_search_type()
    {
        $type = "caca";
        $this->object->set_search_type($type);
        $this->assertEquals(searchEngine_options::RECORD_RECORD, $this->object->get_search_type());
        $type = searchEngine_options::RECORD_RECORD;
        $this->object->set_search_type($type);
        $this->assertEquals(searchEngine_options::RECORD_RECORD, $this->object->get_search_type());
        $type = searchEngine_options::RECORD_GROUPING;
        $this->object->set_search_type($type);
        $this->assertEquals(searchEngine_options::RECORD_GROUPING, $this->object->get_search_type());
    }

    public function testGet_search_type()
    {
        $type = "caca";
        $this->object->set_search_type($type);
        $this->assertEquals(searchEngine_options::RECORD_RECORD, $this->object->get_search_type());
        $type = searchEngine_options::RECORD_RECORD;
        $this->object->set_search_type($type);
        $this->assertEquals(searchEngine_options::RECORD_RECORD, $this->object->get_search_type());
        $type = searchEngine_options::RECORD_GROUPING;
        $this->object->set_search_type($type);
        $this->assertEquals(searchEngine_options::RECORD_GROUPING, $this->object->get_search_type());
    }

    public function testSet_bases()
    {
        $bases = array_keys(self::$user->ACL()->get_granted_base());
        $this->object->set_bases($bases, self::$user->ACL());
        $this->assertEquals(array_values($bases), array_values($this->object->get_bases()));
    }

    public function testGet_bases()
    {
        $bases = array_keys(self::$user->ACL()->get_granted_base());
        $this->object->set_bases($bases, self::$user->ACL());
        $this->assertEquals(array_values($bases), array_values($this->object->get_bases()));
    }

    public function testSet_fields()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_fields()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSet_status()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_status()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSet_record_type()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_record_type()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSet_min_date()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_min_date()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSet_max_date()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_max_date()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSet_date_fields()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_date_fields()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSerialize()
    {
        $bases = array_keys(self::$user->ACL()->get_granted_base());
        $this->object->set_bases($bases, self::$user->ACL());
        $this->object->set_date_fields(array());
        $this->object->set_locale('fr_FR');
        $this->object->set_max_date(null);
        $this->object->set_min_date(null);
        $this->object->set_record_type(searchEngine_options::TYPE_AUDIO);
        $this->object->set_search_type(searchEngine_options::RECORD_RECORD);
        $this->object->set_sort('Name', 'DESC');
        $this->object->set_status(array());
        $this->object->set_use_stemming(true);
        $this->assertEquals($this->object, unserialize(serialize($this->object)));
    }

    public function testUnserialize()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
