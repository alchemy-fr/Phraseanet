<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\PasswordToken;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PasswordTokenTest extends \PhraseanetTestCase
{
    public function testInvalidTokenIsNotValid()
    {
        $random = $this
            ->getMockBuilder('random')
            ->disableOriginalConstructor()
            ->setMethods(['helloToken'])
            ->getMock();

        $token = \random::generatePassword();

        $random
            ->expects($this->once())
            ->method('helloToken')
            ->with($token)
            ->will($this->throwException(new NotFoundHttpException('Token not found')));

        $constraint = new PasswordToken($random);
        $this->assertFalse($constraint->isValid($token));
    }

    public function testValidTokenIsValid()
    {
        $random = $this
            ->getMockBuilder('random')
            ->disableOriginalConstructor()
            ->setMethods(['helloToken'])
            ->getMock();

        $token = \random::generatePassword();

        $random
            ->expects($this->once())
            ->method('helloToken')
            ->with($token)
            ->will($this->returnValue(['usr_id' => mt_rand(), 'type' => \random::TYPE_PASSWORD]));

        $constraint = new PasswordToken($random);
        $this->assertTrue($constraint->isValid($token));
    }
}
