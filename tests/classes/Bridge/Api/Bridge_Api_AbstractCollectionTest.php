<?php

require_once __DIR__ . '/../Bridge_datas.inc';

class Bridge_Api_AbstractCollectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Bridge_Api_AbstractCollection
     */
    protected $object;
    protected $stub;

    public function setUp()
    {
        parent::setUp();
        $this->stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
    }

    public function testGet_total_items()
    {
        $this->assertNull($this->stub->get_total_items());
        $this->stub->set_total_items("3");
        $this->assertEquals(3, $this->stub->get_total_items());
    }

    public function testSet_total_items()
    {
        $return = $this->stub->set_total_items("3");
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->stub->get_total_items());
        $this->assertEquals(3, $this->stub->get_total_items());
        $this->assertEquals($return, $this->stub);
    }

    public function testGet_items_per_page()
    {
        $this->assertNull($this->stub->get_items_per_page());
        $this->stub->set_items_per_page("3");
        $this->assertEquals(3, $this->stub->get_items_per_page());
    }

    public function testSet_items_per_page()
    {
        $return = $this->stub->set_items_per_page("3");
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->stub->get_items_per_page());
        $this->assertEquals(3, $this->stub->get_items_per_page());
        $this->assertEquals($return, $this->stub);
    }

    public function testGet_current_page()
    {
        $this->assertEquals(1, $this->stub->get_current_page());
        $this->stub->set_current_page("3");
        $this->assertEquals(3, $this->stub->get_current_page());
    }

    public function testSet_current_page()
    {
        $return = $this->stub->set_current_page("3");
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->stub->get_current_page());
        $this->assertEquals(3, $this->stub->get_current_page());
        $this->assertEquals($return, $this->stub);
        $return = $this->stub->set_current_page(-4);
        $this->assertEquals(3, $this->stub->get_current_page());
    }

    public function testGet_total_page()
    {
        $this->assertEquals(1, $this->stub->get_total_page());
        $this->stub->set_total_page("3");
        $this->assertEquals(3, $this->stub->get_total_page());
    }

    public function testSet_total_page()
    {
        $return = $this->stub->set_total_page("3");
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->stub->get_total_page());
        $this->assertEquals(3, $this->stub->get_total_page());
        $this->assertEquals($return, $this->stub);
        $return = $this->stub->set_total_page(-4);
        $this->assertEquals(3, $this->stub->get_total_page());
    }

    public function testHas_next_page()
    {
        $this->stub->set_current_page(2);
        $this->stub->set_total_page(2);
        $this->assertFalse($this->stub->has_next_page());
        $this->stub->set_current_page(1);
        $this->stub->set_total_page(2);
        $this->assertTrue($this->stub->has_next_page());
        $this->stub->set_current_page(3);
        $this->stub->set_total_page(2);
        $this->assertFalse($this->stub->has_next_page());
    }

    public function testHas_previous_page()
    {
        $this->stub->set_current_page(2);
        $this->assertTrue($this->stub->has_previous_page());
        $this->stub->set_current_page(1);
        $this->assertFalse($this->stub->has_previous_page());
        $this->stub->set_current_page(0);
        $this->assertFalse($this->stub->has_previous_page());
    }

    public function testHas_more_than_one_page()
    {
        $this->stub->set_total_page(2);
        $this->assertTrue($this->stub->has_more_than_one_page());
        $this->stub->set_total_page(1);
        $this->assertFalse($this->stub->has_more_than_one_page());
        $this->stub->set_total_page(0);
        $this->assertFalse($this->stub->has_more_than_one_page());
    }

    public function testGet_elements()
    {
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $this->stub->get_elements());
        $this->assertEquals(array(), $this->stub->get_elements());
    }
}
