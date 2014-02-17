<?php

namespace Alchemy\Tests\Phrasea\Authentication\Token;

use Alchemy\Phrasea\Authentication\Token\TokenValidator;

class TokenValidatorTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\Authentication\TokenValidator::isValid
     */
    public function testValidTokenIsValid()
    {
        $usr_id = 42;
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_VALIDATE, $usr_id);

        $validator = new TokenValidator(self::$DI['app']['tokens']);
        $this->assertEquals($usr_id, $validator->isValid($token));
    }
    /**
     * @covers Alchemy\Phrasea\Authentication\TokenValidator::isValid
     */
    public function testInvalidTokenIsNotValid()
    {
        $usr_id = 42;
        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_VALIDATE, $usr_id, new \DateTime('-2 hours'));

        $validator = new TokenValidator(self::$DI['app']['tokens']);
        $this->assertFalse($validator->isValid($token));
    }
}
