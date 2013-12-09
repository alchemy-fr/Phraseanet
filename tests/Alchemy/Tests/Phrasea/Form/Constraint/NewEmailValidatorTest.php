<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\NewEmailValidator;

class NewEmailValidatorTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @dataProvider provideValidationData
     */
    public function testValidate($value, $alreadyRegistered)
    {
        $context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $builder = $context
            ->expects($this->exactly($alreadyRegistered ? 1 : 0))
            ->method('addViolation');

        if ($alreadyRegistered) {
            $builder->with($this->isType('string'));
        }

        $validator = new NewEmailValidator();
        $validator->initialize($context);

        $constraint = $this->getConstraint();
        $constraint
            ->expects($this->once())
            ->method('isAlreadyRegistered')
            ->with($value)
            ->will($this->returnValue($alreadyRegistered));

        $validator->validate($value, $constraint);
    }

    public function provideValidationData()
    {
        return [
            ['romain@neutron.io', true],
            ['romain@neutron.io', false],
            ['', false],
            [null, false],
        ];
    }

    private function getConstraint()
    {
        return $this
            ->getMockBuilder('Alchemy\Phrasea\Form\Constraint\NewEmail')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
