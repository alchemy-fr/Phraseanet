<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\GeonameValidator;

class GeonameValidatorTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @dataProvider provideData
     */
    public function testValidate($valid)
    {
        $context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $builder = $context
            ->expects($this->exactly($valid ? 0 : 1))
            ->method('addViolation');

        if (!$valid) {
            $builder->with($this->isType('string'));
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

    public function provideData()
    {
        return array(
            array(true),
            array(false),
        );
    }

    private function getConstraint()
    {
        return $this
            ->getMockBuilder('Alchemy\Phrasea\Form\Constraint\Geoname')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
