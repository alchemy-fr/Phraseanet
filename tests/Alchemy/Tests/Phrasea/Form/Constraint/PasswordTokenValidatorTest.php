<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\PasswordTokenValidator;

class PasswordTokenValidatorTest extends \PhraseanetPHPUnitAbstract
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
        return [
            [\random::generatePassword(), true],
            [\random::generatePassword(), false],
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
