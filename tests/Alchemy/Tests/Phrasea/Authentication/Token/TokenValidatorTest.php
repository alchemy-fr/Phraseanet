<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Token\TokenValidator;

class TokenValidatorTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\Authentication\TokenValidator::isValid
     */
    public function testValidTokenIsValid()
    {
        $app = self::$DI['app'];
        $usr_id = 42;

        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_VALIDATE, $usr_id);

        $validator = new TokenValidator($app);
        $this->assertEquals($usr_id, $validator->isValid($token));
    }
    /**
     * @covers Alchemy\Phrasea\Authentication\TokenValidator::isValid
     */
    public function testInvalidTokenIsNotValid()
    {
        $app = self::$DI['app'];
        $usr_id = 42;

        $token = self::$DI['app']['tokens']->getUrlToken(\random::TYPE_VALIDATE, $usr_id, new \DateTime('-2 hours'));

        $validator = new TokenValidator($app);
        $this->assertFalse($validator->isValid($token));
    }
}
