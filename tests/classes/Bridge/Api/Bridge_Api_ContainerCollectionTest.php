<?php

require_once __DIR__ . '/../Bridge_datas.inc';

class Bridge_Api_ContainerCollectionTest extends PHPUnit_Framework_TestCase
{

    public function testAdd_element()
    {
        $collection = new Bridge_Api_ContainerCollection();
        $i = 0;
        while ($i < 5) {
            $container = $this->getMock("Bridge_Api_ContainerInterface");
            $collection->add_element(new $container);
            $i ++;
        }
        $this->assertEquals(5, sizeof($collection->get_elements()));
    }
}
