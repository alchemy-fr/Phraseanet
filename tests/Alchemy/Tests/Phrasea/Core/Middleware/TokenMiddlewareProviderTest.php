<?php

namespace Alchemy\Tests\Phrasea\Core\Middleware;

use Alchemy\Phrasea\Core\Middleware\TokenMiddlewareProvider;
use Symfony\Component\HttpFoundation\Request;

class TokenMiddlewareProviderTest extends MiddlewareProviderTestCase
{
    public function provideDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Middleware\TokenMiddlewareProvider',
                'middleware.token.converter'
            ],
        ];
    }

    public function testConverterWithNoParameter()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['app']->register(new TokenMiddlewareProvider());
        $request = new Request();
        call_user_func(self::$DI['app']['middleware.token.converter'], $request, self::$DI['app']);
        $this->assertNull($request->attributes->get('token'));
    }

    public function testConverterWithBasketParameter()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['app']->register(new TokenMiddlewareProvider());
        $request = new Request();
        $token = self::$DI['token_1'];
        $request->attributes->set('token', $token->getValue());
        call_user_func(self::$DI['app']['middleware.token.converter'], $request, self::$DI['app']);
        $this->assertSame($token, $request->attributes->get('token'));
    }
}
