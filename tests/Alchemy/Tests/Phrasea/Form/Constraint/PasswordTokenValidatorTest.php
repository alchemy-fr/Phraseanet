<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\PasswordTokenValidator;
use RandomLib\Factory;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PasswordTokenValidatorTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideVoteData')]
    public function testValidate($value, $isValid)
    {
        $context = $this->createMock('Symfony\Component\Validator\ExecutionContextInterface');
        $builder = $context
            ->expects($this->exactly($isValid ? 0 : 1))
            ->method('addViolation');

        if (!$isValid) {
            $builder->with($this->isString());
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

    public static function provideVoteData()
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
        return $this->createMock('Alchemy\Phrasea\Form\Constraint\PasswordToken');
    }
}
