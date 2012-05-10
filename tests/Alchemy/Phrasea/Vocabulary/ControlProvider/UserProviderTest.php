<?php

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class UserProviderTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var UserProvider
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new \Alchemy\Phrasea\Vocabulary\ControlProvider\UserProvider();
    }

    /**
     * Verify that Type is scalar and that the classname is like {Type}Provider
     */
    public function testGetType()
    {
        $type = $this->object->getType();

        $this->assertTrue(is_scalar($type));

        $classname = array_pop(explode('\\', get_class($this->object)));

        $this->assertEquals($classname, $type . 'Provider');
    }

    public function testGetName()
    {
        $this->assertTrue(is_scalar($this->object->getName()));
    }

    public function testFind()
    {
        $results = $this->object->find('BABE', self::$user, self::$collection->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);

        $results = $this->object->find(self::$user->get_email(), self::$user, self::$collection->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);

        $results = $this->object->find(self::$user->get_firstname(), self::$user, self::$collection->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);

        $results = $this->object->find(self::$user->get_lastname(), self::$user, self::$collection->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);
    }

    public function testValidate()
    {
        $this->assertFalse($this->object->validate(-200));
        $this->assertFalse($this->object->validate('A'));
        $this->assertTrue($this->object->validate(self::$user->get_id()));
    }

    public function testGetValue()
    {
        try {
            $this->object->getValue(-200);
            $this->fail('Should raise an exception');
        } catch (\Exception $e) {

        }

        try {
            $this->object->getValue('A');
            $this->fail('Should raise an exception');
        } catch (\Exception $e) {

        }

        $this->assertEquals(self::$user->get_display_name(), $this->object->getValue(self::$user->get_id()));
    }
}
