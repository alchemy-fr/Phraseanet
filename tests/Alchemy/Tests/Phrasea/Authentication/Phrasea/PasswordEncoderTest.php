<?php

namespace Alchemy\Tests\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PasswordEncoderTest extends \PhraseanetTestCase
{
    public static function providePasswords()
    {
        return [
            [
                'foo-key', 'foo', 'bar',
                '116a409f4597bf3ccfe8bc4529c638452c9831d941355f9b49386e6733db31138b228d52fd50409af8960d8059fd03b6c128884efff05055beada86d1ea9a025'
            ],
            [
                'foo-key', 'baz', 'bar',
                'f77b2da9276efd3e4ca66503cf50f4399798968731521c3e71758bb412737d57f2370144fd3adc3a740f87bee9b04b4369018d549c221bc28fcf7967c6712302'
            ],
        ];
    }

    public static function provideInvalidKeys()
    {
        return [
            [null],
            [''],
            ['  '],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidKeys')]
    public function testFailureIfNoKey($key)
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        new PasswordEncoder($key);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePasswords')]
    public function testEncodePassword($key, $pass, $salt, $expected)
    {
        $encoder = new PasswordEncoder($key);
        $this->assertEquals($expected, $encoder->encodePassword($pass, $salt));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePasswords')]
    public function testIsPasswordValid($key, $pass, $salt, $encoded)
    {
        $encoder = new PasswordEncoder($key);
        $this->assertTrue($encoder->isPasswordValid($encoded, $pass, $salt));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePasswords')]
    public function testPasswordNotValid($key, $pass, $salt, $encoded)
    {
        $encoder = new PasswordEncoder($key);
        $this->assertFalse($encoder->isPasswordValid(mt_rand(), $pass, $salt));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePasswords')]
    public function testPasswordNotValidIfChangingTheKey($key, $pass, $salt, $encoded)
    {
        $encoder = new PasswordEncoder($key . mt_rand());
        $this->assertFalse($encoder->isPasswordValid($encoded, $pass, $salt));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePasswords')]
    public function testPasswordNotValidIfChangingTheSalt($key, $pass, $salt, $encoded)
    {
        $encoder = new PasswordEncoder($key);
        $this->assertFalse($encoder->isPasswordValid($encoded, $pass, $salt. mt_rand()));
    }
}
