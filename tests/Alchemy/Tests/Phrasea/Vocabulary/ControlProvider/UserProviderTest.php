<?php

namespace Alchemy\Tests\Phrasea\Vocabulary\ControllerProvider;

use Alchemy\Phrasea\Vocabulary\ControlProvider\UserProvider;

class UserProviderTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var UserProvider
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new \Alchemy\Phrasea\Vocabulary\ControlProvider\UserProvider(self::$DI['app']);
    }

    /**
     * Verify that Type is scalar and that the classname is like {Type}Provider
     */
    public function testGetType()
    {
        $type = $this->object->getType();

        $this->assertTrue(is_scalar($type));

        $data = explode('\\', get_class($this->object));
        $classname = array_pop($data);

        $this->assertEquals($classname, $type . 'Provider');
    }

    public function testGetName()
    {
        $this->assertTrue(is_scalar($this->object->getName()));
    }

    public function testFind()
    {
        $results = $this->object->find('BABE', self::$DI['user'], self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);

        $results = $this->object->find(self::$DI['user']->get_email(), self::$DI['user'], self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);

        $results = $this->object->find(self::$DI['user']->get_firstname(), self::$DI['user'], self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);

        $results = $this->object->find(self::$DI['user']->get_lastname(), self::$DI['user'], self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);
    }

    public function testValidate()
    {
        $this->assertFalse($this->object->validate(-200));
        $this->assertFalse($this->object->validate('A'));
        $this->assertTrue($this->object->validate(self::$DI['user']->get_id()));
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

        $this->assertEquals(self::$DI['user']->get_display_name(), $this->object->getValue(self::$DI['user']->get_id()));
    }
}
