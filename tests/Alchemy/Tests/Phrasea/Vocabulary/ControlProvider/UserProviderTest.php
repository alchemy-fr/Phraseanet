<?php

namespace Alchemy\Tests\Phrasea\Vocabulary\ControlProvider;

use Alchemy\Phrasea\Vocabulary\ControlProvider\UserProvider;
use Doctrine\ORM\EntityManager;

class UserProviderTest extends \PhraseanetTestCase
{
    /**
     * @var UserProvider
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new UserProvider(self::$DI['app']);
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
        // mandatory until user rights are managed by doctrine
        self::$DI['app']['EM'] = EntityManager::create(self::$DI['app']['conf']->get(['main', 'database']), self::$DI['app']['EM.config'], self::$DI['app']['EM.events-manager']);

        $user = self::$DI['app']['manipulator.user']->createUser(uniqid('test'), 'a_password', uniqid('test').'@domain.fr');
        self::giveRightsToUser(self::$DI['app'], $user);
        $user->setFirstName('John');
        $user->setLastName('Doe');
        self::$DI['app']['EM']->persist($user);
        self::$DI['app']['EM']->flush();

        $results = $this->object->find('BABE', $user,  self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);

        $results = $this->object->find($user->getEmail(), $user, self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);

        $results = $this->object->find($user->getFirstName(), $user,  self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);

        $results = $this->object->find($user->getLastName(), $user,  self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);
        self::$DI['app']['model.user-manager']->delete($user);
    }

    public function testValidate()
    {
        $this->assertFalse($this->object->validate(-200));
        $this->assertFalse($this->object->validate('A'));
        $this->assertTrue($this->object->validate(self::$DI['user']->getId()));
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

        $this->assertEquals(self::$DI['user']->getDisplayName(), $this->object->getValue(self::$DI['user']->getId()));
    }
}
