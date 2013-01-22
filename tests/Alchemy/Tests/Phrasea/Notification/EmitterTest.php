<?php

namespace Alchemy\Tests\Phrasea\Notification;

use Alchemy\Phrasea\Notification\Emitter;

class EmitterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Emitter
     */
    private $object;
    private $name;
    private $email;

    protected function setUp()
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
        $user = $this->getMockBuilder('\User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('get_display_name')
            ->will($this->returnValue($this->name));

        $user->expects($this->any())
            ->method('get_email')
            ->will($this->returnValue($this->email));

        $object = Emitter::fromUser($user);
        $this->assertEquals($this->email, $object->getEmail());
        $this->assertEquals($this->name, $object->getName());
    }
}
