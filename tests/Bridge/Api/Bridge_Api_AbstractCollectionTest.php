<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../Bridge_datas.inc';

class Bridge_Api_AbstractCollectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Bridge_Api_AbstractCollection
     */
    protected $object;

    public function testGet_total_items()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $this->assertNull($stub->get_total_items());
        $stub->set_total_items("3");
        $this->assertEquals(3, $stub->get_total_items());
    }

    public function testSet_total_items()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $return = $stub->set_total_items("3");
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $stub->get_total_items());
        $this->assertEquals(3, $stub->get_total_items());
        $this->assertEquals($return, $stub);
    }

    public function testGet_items_per_page()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $this->assertNull($stub->get_items_per_page());
        $stub->set_items_per_page("3");
        $this->assertEquals(3, $stub->get_items_per_page());
    }

    public function testSet_items_per_page()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $return = $stub->set_items_per_page("3");
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $stub->get_items_per_page());
        $this->assertEquals(3, $stub->get_items_per_page());
        $this->assertEquals($return, $stub);
    }

    public function testGet_current_page()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $this->assertEquals(1, $stub->get_current_page());
        $stub->set_current_page("3");
        $this->assertEquals(3, $stub->get_current_page());
    }

    public function testSet_current_page()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $return = $stub->set_current_page("3");
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $stub->get_current_page());
        $this->assertEquals(3, $stub->get_current_page());
        $this->assertEquals($return, $stub);
        $return = $stub->set_current_page(-4);
        $this->assertEquals(3, $stub->get_current_page());
    }

    public function testGet_total_page()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $this->assertEquals(1, $stub->get_total_page());
        $stub->set_total_page("3");
        $this->assertEquals(3, $stub->get_total_page());
    }

    public function testSet_total_page()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $return = $stub->set_total_page("3");
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $stub->get_total_page());
        $this->assertEquals(3, $stub->get_total_page());
        $this->assertEquals($return, $stub);
        $return = $stub->set_total_page(-4);
        $this->assertEquals(3, $stub->get_total_page());
    }

    public function testHas_next_page()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $stub->set_current_page(2);
        $stub->set_total_page(2);
        $this->assertFalse($stub->has_next_page());
        $stub->set_current_page(1);
        $stub->set_total_page(2);
        $this->assertTrue($stub->has_next_page());
        $stub->set_current_page(3);
        $stub->set_total_page(2);
        $this->assertFalse($stub->has_next_page());
    }

    public function testHas_previous_page()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $stub->set_current_page(2);
        $this->assertTrue($stub->has_previous_page());
        $stub->set_current_page(1);
        $this->assertFalse($stub->has_previous_page());
        $stub->set_current_page(0);
        $this->assertFalse($stub->has_previous_page());
    }

    public function testHas_more_than_one_page()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $stub->set_total_page(2);
        $this->assertTrue($stub->has_more_than_one_page());
        $stub->set_total_page(1);
        $this->assertFalse($stub->has_more_than_one_page());
        $stub->set_total_page(0);
        $this->assertFalse($stub->has_more_than_one_page());
    }

    public function testGet_elements()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_AbstractCollection');
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $stub->get_elements());
        $this->assertEquals(array(), $stub->get_elements());
    }
}
