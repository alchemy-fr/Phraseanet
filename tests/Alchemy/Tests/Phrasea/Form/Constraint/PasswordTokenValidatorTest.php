<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\PasswordTokenValidator;
use RandomLib\Factory;

class PasswordTokenValidatorTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideValidationData
     */
    public function testValidate($value, $isValid)
    {
        $context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $builder = $context
            ->expects($this->exactly($isValid ? 0 : 1))
            ->method('addViolation');

        if (!$isValid) {
            $builder->with($this->isType('string'));
        }

        $validator = new PasswordTokenValidator();
        $validator->initialize($context);

        $constraint = $this->getConstraint();
        $constraint
            ->expects($this->once())
            ->method('isValid')
            ->with($value)
            ->will($this->returnValue($isValid));

        $validator->validate($value, $constraint);
    }

    public function provideValidationData()
    {
        $factory = new Factory();
        $generator = $factory->getLowStrengthGenerator();

        return [
            [$generator->generateString(8), true],
            [$generator->generateString(8), false],
        ];
    }

    private function getConstraint()
    {
        return $this
            ->getMockBuilder('Alchemy\Phrasea\Form\Constraint\PasswordToken')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
