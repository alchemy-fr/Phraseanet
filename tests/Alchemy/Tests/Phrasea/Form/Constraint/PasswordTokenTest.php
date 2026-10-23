<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\PasswordToken;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PasswordTokenTest extends \PhraseanetTestCase
{
    public function testInvalidTokenIsNotValid()
    {
        $repo = $this->createMock('Doctrine\ORM\EntityRepository');

        $tokenValue = self::$DI['app']['random.low']->generateString(8);

        $repo
            ->expects($this->once())
            ->method('findValidToken')
            ->with($tokenValue)
            ->will($this->returnValue(null));

        $constraint = new PasswordToken($repo);
        $this->assertFalse($constraint->isValid($tokenValue));
    }

    public function testValidTokenIsValid()
    {
        $repo = $this->createMock('Doctrine\ORM\EntityRepository');

        $tokenValue = self::$DI['app']['random.low']->generateString(8);
        $token = new Token();
        $token->setType(TokenManipulator::TYPE_PASSWORD);

        $repo
            ->expects($this->once())
            ->method('findValidToken')
            ->with($tokenValue)
            ->will($this->returnValue($token));

        $constraint = new PasswordToken($repo);
        $this->assertTrue($constraint->isValid($tokenValue));
    }
}
