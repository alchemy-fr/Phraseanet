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

class CookiesDisablerSubscriberTest extends \PHPUnit_Framework_TestCase
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
    }

    public function provideVariousRoutes()
    {
        return array(
            array(false, '/prod'),
            array(false, '/admin'),
            array(false, '/api'),
            array(false, '/api/oauthv2'),
            array(false, '/'),
            array(false, '/datafiles/'),
            array(true, '/permalink'),
            array(true, '/permalink/v1'),
            array(true, '/api/v1'),
            array(true, '/api/v1/'),
            array(true, '/api/v1/records'),
        );
    }
}
