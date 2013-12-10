<?php

namespace Alchemy\Tests\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Form\Constraint\NewEmail;

class NewEmailTest extends \PhraseanetTestCase
{
    public function testAnUnknownAddressIsNotAlreadyRegistered()
    {
        $constraint = new NewEmail(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered('nonehere'));
    }

    public function testARegisteredAddressIsAlreadyRegistered()
    {
        $constraint = new NewEmail(self::$DI['app']);
        $this->assertTrue($constraint->isAlreadyRegistered(self::$DI['user']->get_email()));
    }

    public function testNullIsNotAlreadyRegistered()
    {
        $constraint = new NewEmail(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered(null));
    }

    public function testBlankIsNotAlreadyRegistered()
    {
        $constraint = new NewEmail(self::$DI['app']);
        $this->assertFalse($constraint->isAlreadyRegistered(''));
    }
}
