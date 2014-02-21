<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\NewLogin;

class NewLoginTest extends \PhraseanetTestCase
{
    public function testAnUnknownLoginIsNotAlreadyRegistered()
    {
        $constraint = NewLogin::create(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered('nonehere@test.com'));
    }

    public function testARegisteredLoginIsAlreadyRegistered()
    {
        $constraint = NewLogin::create(self::$DI['app']);
        $this->assertTrue($constraint->isAlreadyRegistered(self::$DI['user']->getLogin()));
    }

    public function testNullIsNotAlreadyRegistered()
    {
        $constraint = NewLogin::create(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered(null));
    }

    public function testBlankIsNotAlreadyRegistered()
    {
        $constraint = NewLogin::create(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered(''));
    }
}
