<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\GeonameValidator;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class GeonameValidatorTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideData')]
    public function testValidate($valid)
    {
        $context = $this->createMock('Symfony\Component\Validator\ExecutionContextInterface');
        $builder = $context
            ->expects($this->exactly($valid ? 0 : 1))
            ->method('addViolation');

        if (!$valid) {
            $builder->with($this->isString());
        }

        $validator = new GeonameValidator();
        $validator->initialize($context);

        $constraint = $this->getConstraint();
        $constraint
            ->expects($this->once())
            ->method('isValid')
            ->with(123456)
            ->will($this->returnValue($valid));

        $validator->validate(123456, $constraint);
    }

    public static function provideData()
    {
        return [
            [true],
            [false],
        ];
    }

    private function getConstraint()
    {
        return $this->createMock('Alchemy\Phrasea\Form\Constraint\Geoname');
    }
}
