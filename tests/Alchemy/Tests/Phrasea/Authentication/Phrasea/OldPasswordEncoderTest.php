<?php

namespace Alchemy\Tests\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder;

class OldPasswordEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providePasswords
     * @covers Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder::encodePassword
     */
    public function testEncodePassword($pass, $salt, $encoded)
    {
        $encoder = new OldPasswordEncoder();
        $this->assertEquals($encoded, $encoder->encodePassword($pass, $salt));
    }

    /**
     * @dataProvider providePasswords
     * @covers Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder::isPasswordValid
     */
    public function testIsPasswordValid($pass, $salt, $encoded)
    {
        $encoder = new OldPasswordEncoder();
        $this->assertTrue($encoder->isPasswordValid($encoded, $pass, $salt));
    }

    /**
     * @dataProvider providePasswords
     * @covers Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder::isPasswordValid
     */
    public function testPasswordNotValid($pass, $salt, $encoded)
    {
        $encoder = new OldPasswordEncoder();
        $this->assertFalse($encoder->isPasswordValid(mt_rand(), $pass, $salt));
    }

    public function providePasswords()
    {
        return [
            ['foo', 'bar', '2c26b46b68ffc68ff99b453c1d30413413422d706483bfa0f98a5e886266e7ae'],
            ['foo', 'bar', '2c26b46b68ffc68ff99b453c1d30413413422d706483bfa0f98a5e886266e7ae'],
            ['bar', 'foo', 'fcde2b2edba56bf408601fb721fe9b5c338d10ee429ea04fae5511b68fbf8fb9'],
            ['bar', 'baz', 'fcde2b2edba56bf408601fb721fe9b5c338d10ee429ea04fae5511b68fbf8fb9'],
        ];
    }
}
