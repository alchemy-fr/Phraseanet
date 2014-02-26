<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\Subscriber\CookiesDisablerSubscriber;
use Alchemy\Phrasea\Application;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class CookiesDisablerSubscriberTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideVariousRoutes
     */
    public function testRoutes($disabled, $route)
    {
        $app = new Application();
        $app['dispatcher']->addSubscriber(new CookiesDisablerSubscriber($app));

        $app->get($route, function () {
           $response = new Response();
           $response->headers->setCookie(new Cookie('key', 'value'));

           return $response;
        });

        $client = $this->getClientWithCookie($app);
        $client->request('GET', $route);

        $this->assertSame($disabled, $app['session.test']);
        if ($disabled) {
            $this->assertCount(0, $client->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY));
        } else {
            $this->assertGreaterThanOrEqual(1, count($client->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY)));
        }
    }

    private function getClientWithCookie(Application $app)
    {
        $cookieJar = new CookieJar();
        $cookieJar->set(new BrowserCookie('test-cookie', 'cookievalue'));

        return new Client($app, [], null, $cookieJar);
    }

    public function provideVariousRoutes()
    {
        return [
            [false, '/prod'],
            [false, '/admin'],
            [true, '/api'],
            [true, '/api/'],
            [false, '/api/oauthv2'],
            [false, '/'],
            [false, '/datafiles/'],
            [true, '/permalink'],
            [true, '/permalink/v1'],
            [true, '/api/v1'],
            [true, '/api/v1/'],
            [true, '/api/v1/records'],
        ];
    }
}
