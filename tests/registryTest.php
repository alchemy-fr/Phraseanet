<?php

require_once __DIR__ . '/PhraseanetPHPUnitAbstract.class.inc';

class registryTest extends PhraseanetPHPUnitAbstract
{

  /**
   * @var registry
   */
  protected $object;

  public function setUp()
  {
    parent::setUp();
    $this->object = registry::get_instance();
  }

  public function testGet()
  {
    $this->testSet();
  }

  public function testSet()
  {
    /**
     * Set value with default type (string)
     */
    $this->object->set('key_test', 'value1', registry::TYPE_STRING);
    $this->assertTrue($this->object->get('key_test') === 'value1');

    $this->object->set('key_test', 1, registry::TYPE_STRING);
    $this->assertTrue($this->object->get('key_test') === '1');

    $this->object->set('key_test', '1', registry::TYPE_STRING);
    $this->assertTrue($this->object->get('key_test') === '1');

    $this->object->set('key_test', array('caca'), registry::TYPE_STRING);
    $this->assertTrue($this->object->get('key_test') === 'Array');


    /**
     * Set value with type (string)
     */
    $this->object->set('key_test', 'value1', registry::TYPE_STRING);
    $this->assertTrue($this->object->get('key_test') === 'value1');

    $this->object->set('key_test', 1, registry::TYPE_STRING);
    $this->assertTrue($this->object->get('key_test') === '1');

    $this->object->set('key_test', '1', registry::TYPE_STRING);
    $this->assertTrue($this->object->get('key_test') === '1');

    $this->object->set('key_test', array('caca'), registry::TYPE_STRING);
    $this->assertTrue($this->object->get('key_test') === 'Array');

    /**
     * Set value with type (int)
     */
    $this->object->set('key_test', 'value1', registry::TYPE_INTEGER);
    $this->assertTrue($this->object->get('key_test') === 0);

    $this->object->set('key_test', 1, registry::TYPE_INTEGER);
    $this->assertTrue($this->object->get('key_test') === 1);

    $this->object->set('key_test', '1', registry::TYPE_INTEGER);
    $this->assertTrue($this->object->get('key_test') === 1);

    $this->object->set('key_test', array('caca'), registry::TYPE_INTEGER);
    $this->assertTrue($this->object->get('key_test') === 1);

    /**
     * Set value with type boolean
     */
    $this->object->set('key_test', 'value1', registry::TYPE_BOOLEAN);
    $this->assertTrue($this->object->get('key_test') === true);

    $this->object->set('key_test', 1, registry::TYPE_BOOLEAN);
    $this->assertTrue($this->object->get('key_test') === true);

    $this->object->set('key_test', '1', registry::TYPE_BOOLEAN);
    $this->assertTrue($this->object->get('key_test') === true);

    $this->object->set('key_test', array('caca'), registry::TYPE_BOOLEAN);
    $this->assertTrue($this->object->get('key_test') === true);

    $this->object->set('key_test', '0', registry::TYPE_BOOLEAN);
    $this->assertTrue($this->object->get('key_test') === false);

    $this->object->set('key_test', 0, registry::TYPE_BOOLEAN);
    $this->assertTrue($this->object->get('key_test') === false);

    $this->object->set('key_test', false, registry::TYPE_BOOLEAN);
    $this->assertTrue($this->object->get('key_test') === false);

    $this->object->set('key_test', true, registry::TYPE_BOOLEAN);
    $this->assertTrue($this->object->get('key_test') === true);

    /**
     * Set value with type array
     */
    $this->object->set('key_test', 'value1', registry::TYPE_ARRAY);
    $this->assertTrue($this->object->get('key_test') === array('value1'));

    $this->object->set('key_test', 1, registry::TYPE_ARRAY);
    $this->assertTrue($this->object->get('key_test') === array(1));

    $this->object->set('key_test', '1', registry::TYPE_ARRAY);
    $this->assertTrue($this->object->get('key_test') === array('1'));

    $this->object->set('key_test', array('caca'), registry::TYPE_ARRAY);
    $this->assertTrue($this->object->get('key_test') === array('caca'));

    $this->object->set('key_test', '0', registry::TYPE_ARRAY);
    $this->assertTrue($this->object->get('key_test') === array('0'));

    $this->object->set('key_test', 0, registry::TYPE_ARRAY);
    $this->assertTrue($this->object->get('key_test') === array(0));

    $this->object->set('key_test', false, registry::TYPE_ARRAY);
    $this->assertTrue($this->object->get('key_test') === array(false));

    $this->object->set('key_test', true, registry::TYPE_ARRAY);
    $this->assertTrue($this->object->get('key_test') === array(true));

    /**
     * Set value with type enum_multi
     */
    $this->object->set('key_test', 'value1', registry::TYPE_ENUM_MULTI);
    $this->assertTrue($this->object->get('key_test') === array('value1'));

    $this->object->set('key_test', 1, registry::TYPE_ENUM_MULTI);
    $this->assertTrue($this->object->get('key_test') === array(1));

    $this->object->set('key_test', '1', registry::TYPE_ENUM_MULTI);
    $this->assertTrue($this->object->get('key_test') === array('1'));

    $this->object->set('key_test', array('caca'), registry::TYPE_ENUM_MULTI);
    $this->assertTrue($this->object->get('key_test') === array('caca'));

    $this->object->set('key_test', '0', registry::TYPE_ENUM_MULTI);
    $this->assertTrue($this->object->get('key_test') === array('0'));

    $this->object->set('key_test', 0, registry::TYPE_ENUM_MULTI);
    $this->assertTrue($this->object->get('key_test') === array(0));

    $this->object->set('key_test', false, registry::TYPE_ENUM_MULTI);
    $this->assertTrue($this->object->get('key_test') === array(false));

    $this->object->set('key_test', true, registry::TYPE_ENUM_MULTI);
    $this->assertTrue($this->object->get('key_test') === array(true));
  }

  public function testIs_set()
  {
    $this->object->set('key_test', 'value', registry::TYPE_STRING);
    $this->assertTrue($this->object->is_set('key_test'));
    $this->assertFalse($this->object->is_set('keifgjkqskodfqsflqkspfoqsfp'));
  }

  public function testUn_set()
  {
    $this->testIs_set();
    $this->object->un_set('key_test');
    $this->assertFalse($this->object->is_set('key_test'));
  }

}

