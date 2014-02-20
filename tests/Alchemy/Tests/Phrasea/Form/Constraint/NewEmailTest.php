<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\NewEmail;

class NewEmailTest extends \PhraseanetTestCase
{
    public function testAnUnknownAddressIsNotAlreadyRegistered()
    {
        $constraint = NewEmail::create(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered('nonehere'));
    }

    public function testARegisteredAddressIsAlreadyRegistered()
    {
        $constraint = NewEmail::create(self::$DI['app']);
        $this->assertTrue($constraint->isAlreadyRegistered(self::$DI['user']->getEmail()));
    }

    public function testNullIsNotAlreadyRegistered()
    {
        $constraint = NewEmail::create(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered(null));
    }

    public function testBlankIsNotAlreadyRegistered()
    {
        $constraint = NewEmail::create(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered(''));
    }
}
