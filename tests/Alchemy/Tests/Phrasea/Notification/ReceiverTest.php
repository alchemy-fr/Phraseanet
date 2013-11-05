<?php

namespace Alchemy\Tests\Phrasea\Notification;

use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

class ReceiverTest extends \PhraseanetTestCase
{
    /**
     * @var Receiver
     */
    protected $object;
    private $name;
    private $email;

    public function setUp()
    {
        $this->name = 'name-' . mt_rand();
        $this->email = sprintf('name-%s@domain-%s.com', mt_rand(), mt_rand());
        $this->object = new Receiver($this->name, $this->email);
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Receiver::getName
     */
    public function testGetName()
    {
        $this->assertEquals($this->name, $this->object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Receiver::getEmail
     */
    public function testGetEmail()
    {
        $this->assertEquals($this->email, $this->object->getEmail());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Receiver::fromUser
     */
    public function testFromUser()
    {
        $user = $this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue($this->name));

        $user->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue($this->email));

        $object = Receiver::fromUser($user);
        $this->assertEquals($this->email, $object->getEmail());
        $this->assertEquals($this->name, $object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Receiver::fromUser
     */
    public function testFromUserFailed()
    {
        $user = $this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue($this->name));

        $user->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('wrong user'));

        try {
            Receiver::fromUser($user);
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
        new Receiver('romain neutron', 'romain neutron email');
    }
}
