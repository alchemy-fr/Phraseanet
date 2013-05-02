<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\NewLogin;

class NewLoginTest extends \PhraseanetPHPUnitAbstract
{
    public function testAnUnknownLoginIsNotAlreadyRegistered()
    {
        $constraint = new NewLogin(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered('nonehere@test.com'));
    }

    public function testARegisteredLoginIsAlreadyRegistered()
    {
        $constraint = new NewLogin(self::$DI['app']);
        $this->assertTrue($constraint->isAlreadyRegistered(self::$DI['user']->get_login()));
    }

    public function testNullIsNotAlreadyRegistered()
    {
        $constraint = new NewLogin(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered(null));
    }

    public function testBlankIsNotAlreadyRegistered()
    {
        $constraint = new NewLogin(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered(''));
    }
}
