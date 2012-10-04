<?php

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Component\BrowserKit\CookieJar;

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ApplicationRootTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{
    public function testRouteSlash()
    {
        $crawler = self::$DI['client']->request('GET', '/');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertRegExp('/^\/login\/\?redirect=[\/a-zA-Z]+/', $response->headers->get('location'));
    }

    public function testRouteRobots()
    {
        $original_value = self::$DI['app']['phraseanet.registry']->get('GV_allow_search_engine');

        self::$DI['app']['phraseanet.registry']->set('GV_allow_search_engine', false, \registry::TYPE_BOOLEAN);

        $crawler = self::$DI['client']->request('GET', '/robots.txt');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('UTF-8', $response->getCharset());

        $this->assertRegExp('/^Disallow: \/$/m', $response->getContent());

        self::$DI['app']['phraseanet.registry']->set('GV_allow_search_engine', true, \registry::TYPE_BOOLEAN);

        $crawler = self::$DI['client']->request('GET', '/robots.txt');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('UTF-8', $response->getCharset());

        $this->assertRegExp('/^Allow: \/$/m', $response->getContent());

        self::$DI['app']['phraseanet.registry']->set('GV_allow_search_engine', $original_value, \registry::TYPE_BOOLEAN);
    }

    public function testNoPersistentCookie()
    {
        $app = self::$DI['app'];
        $app->closeAccount();

        $boolean = false;

        $app->get('/unit-test-route', function(Application $app) use (&$boolean) {
            $boolean = $app->isAuthenticated();
        });

        $client = new Client($app);
        $client->request('GET', '/unit-test-route');

        $this->assertFalse($boolean);
    }

    public function testPersistentCookie()
    {
        $app = self::$DI['app'];
        $app->closeAccount();

        $browser = $this->getMockBuilder('\Browser')
            ->disableOriginalConstructor()
            ->getMock();

        $browser->expects($this->any())
            ->method('getBrowser')
            ->will($this->returnValue('Un joli browser'));

        $browser->expects($this->any())
            ->method('getPlatform')
            ->will($this->returnValue('Une belle version'));

        $nonce = \random::generatePassword(16);
        $string = $browser->getBrowser() . '_' . $browser->getPlatform();

        $token = \User_Adapter::salt_password($app, $string, $nonce);

        $app['browser'] = $browser;

        $session = new \Entities\Session();
        $session->setUser(self::$DI['user'])
            ->setBrowserName($browser->getBrowser())
            ->setBrowserVersion($browser->getVersion())
            ->setUserAgent('Custom UA')
            ->setNonce($nonce)
            ->setToken($token);

        $app['EM']->persist($session);
        $app['EM']->flush();

        $boolean = false;

        $app->get('/unit-test-route', function(Application $app) use (&$boolean) {
            $boolean = $app->isAuthenticated();
        });

        $cookieJar = new CookieJar();
        $cookieJar->set(new BrowserCookie('persistent', $token));

        $client = new Client($app, array(), null, $cookieJar);
        $client->request('GET', '/unit-test-route');

        $this->assertTrue($boolean);
    }
}
