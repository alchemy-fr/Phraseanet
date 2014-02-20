<?php

namespace Alchemy\Tests\Phrasea\Notification;

use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

class EmitterTest extends \PhraseanetTestCase
{
    /**
     * @var Emitter
     */
    private $object;
    private $name;
    private $email;

    public function setUp()
    {
        $this->name = 'name-' . mt_rand();
        $this->email = sprintf('name-%s@domain-%s.com', mt_rand(), mt_rand());
        $this->object = new Emitter($this->name, $this->email);
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Emitter::getName
     */
    public function testGetName()
    {
        $this->assertEquals($this->name, $this->object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Emitter::getEmail
     */
    public function testGetEmail()
    {
        $this->assertEquals($this->email, $this->object->getEmail());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Emitter::fromUser
     */
    public function testFromUser()
    {
        $user = $this->createUserMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue($this->name));

        $user->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue($this->email));

        $object = Emitter::fromUser($user);
        $this->assertEquals($this->email, $object->getEmail());
        $this->assertEquals($this->name, $object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Emitter::fromUser
     */
    public function testFromUserFails()
    {
        $user = $this->createUserMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue($this->name));

        $user->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('wrong email'));

        try {
            Emitter::fromUser($user);
            $this->fail('Should have raised an exception');
        } catch (InvalidArgumentException $e) {

        }
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid e-mail address (romain neutron email)
     */
    public function testWrongEmail()
    {
        new Emitter('romain neutron', 'romain neutron email');
    }
}
