<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\NewEmailValidator;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class NewEmailValidatorTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideVoteData')]
    public function testValidate($value, $alreadyRegistered)
    {
        $context = $this->createMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $builder = $context
            ->expects($this->exactly($alreadyRegistered ? 1 : 0))
            ->method('addViolation');

        if ($alreadyRegistered) {
            $builder->with($this->isString());
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

    public static function provideVoteData()
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
        return $this->createMock('Alchemy\Phrasea\Form\Constraint\NewEmail');
    }
}
