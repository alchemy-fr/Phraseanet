<?php

namespace Alchemy\Tests\Phrasea\Core\Middleware;

use Alchemy\Phrasea\Core\Middleware\BasketMiddlewareProvider;
use Symfony\Component\HttpFoundation\Request;

class BasketMiddlewareProviderTest extends MiddlewareProviderTestCase
{
    public function provideDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Middleware\BasketMiddlewareProvider',
                'middleware.basket.converter'
            ],
            [
                'Alchemy\Phrasea\Core\Middleware\BasketMiddlewareProvider',
                'middleware.basket.user-access'
            ],
            [
                'Alchemy\Phrasea\Core\Middleware\BasketMiddlewareProvider',
                'middleware.basket.user-is-owner'
            ],
        ];
    }

    public function testConverterWithNoParameter()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['app']->register(new BasketMiddlewareProvider());
        $request = new Request();
        call_user_func(self::$DI['app']['middleware.basket.converter'], $request, self::$DI['app']);
        $this->assertNull($request->attributes->get('basket'));
    }

    public function testConverterWithBasketParameter()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['app']->register(new BasketMiddlewareProvider());
        $request = new Request();
        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);
        $request->attributes->set('basket', $basket->getId());
        call_user_func(self::$DI['app']['middleware.basket.converter'], $request, self::$DI['app']);
        $this->assertSame($basket, $request->attributes->get('basket'));
    }

    public function testUserAccessWithNoParameter()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['app']->register(new BasketMiddlewareProvider());
        $request = new Request();
        call_user_func(self::$DI['app']['middleware.basket.user-access'], $request, self::$DI['app']);
        $this->assertNull($request->attributes->get('basket'));
    }

    public function testUserAccessWithBasketOwner()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['app']->register(new BasketMiddlewareProvider());
        $request = new Request();
        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);
        $request->attributes->set('basket', $basket);
        call_user_func(self::$DI['app']['middleware.basket.user-access'], $request, self::$DI['app']);
    }

    public function testUserAccessWithoutBasketOwner()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['app']->register(new BasketMiddlewareProvider());
        $request = new Request();
        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 3);
        $request->attributes->set('basket', $basket);
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException', 'Current user does not have access to the basket');
        call_user_func(self::$DI['app']['middleware.basket.user-access'], $request, self::$DI['app']);
    }
}
