<?php

namespace Alchemy\Phrasea;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;

require __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class ApplicationTest extends \PhraseanetPHPUnitAbstract
{

    public function testLoad()
    {
        $start = microtime(true);
        $app = new Application();
        $duration = microtime(true) - $start;

        $this->assertLessThan(0.02, $duration);
    }

    public function testDebug()
    {
        $app = new Application();
        $this->assertFalse($app['debug']);

        $app = new Application('prod');
        $this->assertFalse($app['debug']);

        $app = new Application('test');
        $this->assertTrue($app['debug']);
    }

    public function testTestLocale()
    {
        $app = new Application();
    }

    public function testCookie()
    {
        $app = $this->getCookieApp();

        $client = $this->getClientWithCookie($app);
        $client->request('GET', '/');

        $response = $client->getResponse();

        $cookies = $response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);

        $this->assertEquals(2, count($cookies['']['/']));
    }

    public function testTestDisableCookie()
    {
        $app = $this->getCookieApp();
        $app->disableCookies();

        $client = $this->getClientWithCookie($app);
        $client->request('GET', '/');

        $response = $client->getResponse();
        $cookies = $response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);
        $this->assertEquals(0, count($cookies));
    }

    public function testOpenAccount()
    {
        $app = new Application('test');

        $this->assertFalse($app->isAuthenticated());
        $app->openAccount($this->getAuthMock());
        $this->assertTrue($app->isAuthenticated());
    }

    public function testCloseAccount()
    {
        $app = new Application('test');

        $this->assertFalse($app->isAuthenticated());
        $app->openAccount($this->getAuthMock());
        $this->assertTrue($app->isAuthenticated());
        $app->closeAccount();
        $this->assertFalse($app->isAuthenticated());
    }

    private function getAuthMock()
    {
        $auth = $this->getMockBuilder('Session_Authentication_Interface')
            ->disableOriginalConstructor()
            ->getMock();
        $auth->expects($this->any())
            ->method('get_user')
            ->will($this->returnValue(self::$DI['user']));

        return $auth;
    }

    private function getCookieApp()
    {
        $app = new Application('test');
        $app->get('/', function(Application $app, Request $request) {

            $app['session']->set('usr_id', 5);

            $response = new Response('hello');
            $response->headers->setCookie(new Cookie('key', 'value'));

            return $response;
        });
        unset($app['exception_handler']);

        return $app;
    }

    private function getClientWithCookie(Application $app)
    {
        $cookieJar = new CookieJar();
        $cookieJar->set(new BrowserCookie('locale', 'fr_FR'));

        return new Client($app, array(), null, $cookieJar);
    }
}
