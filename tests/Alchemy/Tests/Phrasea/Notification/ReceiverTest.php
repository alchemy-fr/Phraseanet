<?php

namespace Alchemy\Tests\Phrasea\Notification;

use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ReceiverTest extends \PhraseanetTestCase
{
    /**
     * @var Receiver
     */
    protected $object;
    private $name;
    private $email;

    public function setUp(): void
    {
        parent::setUp();
        $this->name = 'name-' . mt_rand();
        $this->email = sprintf('name-%s@domain-%s.com', mt_rand(), mt_rand());
        $this->object = new Receiver($this->name, $this->email);
    }

    public function testGetName()
    {
        $this->assertEquals($this->name, $this->object->getName());
    }

    public function testGetEmail()
    {
        $this->assertEquals($this->email, $this->object->getEmail());
    }

    public function testFromUser()
    {
        $user = $this->createUserMock();

        $user
            ->method('getDisplayName')
            ->will($this->returnValue($this->name));

        $user
            ->method('getEmail')
            ->will($this->returnValue($this->email));

        $object = Receiver::fromUser($user);
        $this->assertEquals($this->email, $object->getEmail());
        $this->assertEquals($this->name, $object->getName());
    }

    public function testFromUserFailed()
    {
        $user = $this->createUserMock();

        $user
            ->method('getDisplayName')
            ->will($this->returnValue($this->name));

        $user
            ->method('getEmail')
            ->will($this->returnValue('wrong user'));

        try {
            Receiver::fromUser($user);
            $this->fail('Should have raised an exception');
        } catch (InvalidArgumentException $e) {

        }
    }

    public function testWrongEmail()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid e-mail address (romain neutron email)');
        new Receiver('romain neutron', 'romain neutron email');
    }
}
