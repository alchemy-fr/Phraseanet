<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Component\BrowserKit\CookieJar;

class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    public function testRouteSetLocale()
    {
        $cookieJar = new CookieJar();
        $cookieJar->set(new BrowserCookie('locale', 'de'));

        $client = new Client(self::$DI['app'], [], null, $cookieJar);
        $crawler = $client->request('GET', '/language/fr_CA/');

        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());

        $found = false;

        foreach ($response->headers->getCookies() as $cookie) {
            if ('locale' === $cookie->getName()) {
                if ('fr_CA' === $cookie->getValue()) {
                    $found = true;
                }
                break;
            }
        }

        if (!$found) {
            $this->fail('Unable to set language');
        }
    }

    public function testRouteSlash()
    {
        $crawler = self::$DI['client']->request('GET', '/');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    public function testRouteAvailableLanguages()
    {
        $crawler = self::$DI['client']->request('GET', '/available-languages');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals((array) json_decode($response->getContent()), self::$DI['app']['locales.available']);
    }

    public function testRouteRobots()
    {
        $original_value = self::$DI['app']['conf']->get(['registry', 'general', 'allow-indexation']);

        self::$DI['app']['conf']->set(['registry', 'general', 'allow-indexation'], false);

        $crawler = self::$DI['client']->request('GET', '/robots.txt');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('UTF-8', $response->getCharset());

        $this->assertRegExp('/^Disallow: \/$/m', $response->getContent());

        self::$DI['app']['conf']->set(['registry', 'general', 'allow-indexation'], true);

        $crawler = self::$DI['client']->request('GET', '/robots.txt');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('UTF-8', $response->getCharset());

        $this->assertRegExp('/^Allow: \/$/m', $response->getContent());

        self::$DI['app']['conf']->set(['registry', 'general', 'allow-indexation'], $original_value);
    }

    public function testNoPersistentCookie()
    {
        $app = self::$DI['app'];
        $this->logout($app);

        $boolean = false;

        $app->get('/unit-test-route', function (Application $app) use (&$boolean) {
            $boolean = $app['authentication']->isAuthenticated();

            return new Response();
        });

        $client = new Client($app);
        $client->request('GET', '/unit-test-route');

        $this->assertFalse($boolean);
    }

    public function testPersistentCookie()
    {
        $app = self::$DI['app'];
        $this->logout(self::$DI['app']);

        $browser = $this->getMockBuilder('\Browser')
            ->disableOriginalConstructor()
            ->getMock();

        $browser->expects($this->any())
            ->method('getBrowser')
            ->will($this->returnValue('Un joli browser'));

        $browser->expects($this->any())
            ->method('getPlatform')
            ->will($this->returnValue('Une belle version'));

        $nonce = self::$DI['app']['random.low']->generateString(16);
        $string = $browser->getBrowser() . '_' . $browser->getPlatform();

        $token = self::$DI['app']['auth.password-encoder']->encodePassword($string, $nonce);

        $app['browser'] = $browser;

        $session = new \Alchemy\Phrasea\Model\Entities\Session();
        $session->setUser(self::$DI['user'])
            ->setBrowserName($browser->getBrowser())
            ->setBrowserVersion($browser->getVersion())
            ->setUserAgent('Custom UA')
            ->setNonce($nonce)
            ->setToken($token);

        $app['orm.em']->persist($session);
        $app['orm.em']->flush();

        $boolean = false;

        $app->get('/unit-test-route', function (Application $app) use (&$boolean) {
            $boolean = $app['authentication']->isAuthenticated();

            return new Response();
        });

        $cookieJar = new CookieJar();
        $cookieJar->set(new BrowserCookie('persistent', $token));

        $client = new Client($app, [], null, $cookieJar);
        $client->request('GET', '/unit-test-route');

        $this->assertTrue($boolean);
    }
}
