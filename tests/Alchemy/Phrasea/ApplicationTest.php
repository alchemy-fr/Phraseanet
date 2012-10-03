<?php

namespace Alchemy\Phrasea;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class ApplicationTest extends \PhraseanetPHPUnitAbstract
{

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testLoad()
    {
        /**
         * Warm up
         */
        $app = new Application();

        $start = microtime(true);
        $app = new Application();
        $duration = microtime(true) - $start;

        $this->assertLessThan(0.005, $duration);
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testDebug()
    {
        $app = new Application();
        $this->assertFalse($app['debug']);

        $app = new Application('prod');
        $this->assertFalse($app['debug']);

        $app = new Application('test');
        $this->assertTrue($app['debug']);

        $app = new Application('dev');
        $this->assertTrue($app['debug']);
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testTestLocale()
    {
        $app = new Application();
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testCookie()
    {
        $app = $this->getApp();

        $client = $this->getClientWithCookie($app);
        $client->request('GET', '/');

        $response = $client->getResponse();

        $cookies = $response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);

        $this->assertEquals(2, count($cookies['']['/']));
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testTestDisableCookie()
    {
        $app = $this->getApp();
        $app->disableCookies();

        $client = $this->getClientWithCookie($app);
        $client->request('GET', '/');

        $response = $client->getResponse();
        $cookies = $response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);
        $this->assertEquals(0, count($cookies));
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testOpenAccount()
    {
        $app = new Application('test');

        $this->assertFalse($app->isAuthenticated());
        $app->openAccount($this->getAuthMock());
        $this->assertTrue($app->isAuthenticated());
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testCloseAccount()
    {
        $app = new Application('test');

        $this->assertFalse($app->isAuthenticated());
        $app->openAccount($this->getAuthMock());
        $this->assertTrue($app->isAuthenticated());
        $app->closeAccount();
        $this->assertFalse($app->isAuthenticated());
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testCookieLocale()
    {
        $app = $this->getAppThatReturnLocale();

        foreach (array('fr_FR', 'en_GB', 'de_DE') as $locale) {
            $client = $this->getClientWithCookie($app, $locale);
            $client->request('GET', '/');

            $this->assertEquals($locale, $client->getResponse()->getContent());
        }
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testNoCookieLocaleReturnsDefaultLocale()
    {
        $app = $this->getAppThatReturnLocale();
        $this->mockRegistryAndReturnLocale($app, 'en_USA');

        $client = $this->getClientWithCookie($app, null);
        $client->request('GET', '/');

        $this->assertEquals('en_USA', $client->getResponse()->getContent());
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testWrongCookieLocaleReturnsDefaultLocale()
    {
        $app = $this->getAppThatReturnLocale();
        $this->mockRegistryAndReturnLocale($app, 'en_USA');

        $client = $this->getClientWithCookie($app, 'de_PL');
        $client->request('GET', '/');

        $this->assertEquals('en_USA', $client->getResponse()->getContent());
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testNoCookieReturnsContentNegotiated()
    {
        $app = $this->getAppThatReturnLocale();
        $this->mockRegistryAndReturnLocale($app, 'en_USA');

        $client = $this->getClientWithCookie($app, null);
        $client->request('GET', '/', array(), array(), array('accept_language' => 'en-US;q=0.75,en;q=0.8,fr-FR;q=0.9'));

        $this->assertEquals('fr_FR', $client->getResponse()->getContent());
    }

    private function getAppThatReturnLocale()
    {
        $app = new Application('test');

        $app->get('/', function(Application $app, Request $request) {

            return $app['locale'];
        });
        unset($app['exception_handler']);

        return $app;
    }

    private function mockRegistryAndReturnLocale(Application $app, $locale)
    {
        $app['phraseanet.registry'] = $this->getMockBuilder('\registry')
            ->disableOriginalConstructor()
            ->getmock();
        $app['phraseanet.registry']->expects($this->any())
            ->method('get')
            ->will($this->returnValue($locale));
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

    private function getApp()
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

    private function getClientWithCookie(Application $app, $locale = 'fr_FR')
    {
        $cookieJar = new CookieJar();
        if ($locale) {
            $cookieJar->set(new BrowserCookie('locale', $locale));
        }
        return new Client($app, array(), null, $cookieJar);
    }
}
