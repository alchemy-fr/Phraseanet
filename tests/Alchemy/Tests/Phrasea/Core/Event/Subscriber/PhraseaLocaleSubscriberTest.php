<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Silex\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaLocaleSubscriber;
use Neutron\Silex\Provider\BadFaithServiceProvider;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;

class PhraseaLocaleSubscriberTest extends \PhraseanetPHPUnitAbstract
{
    public function testBasic()
    {
        $app = new Application();

        $app->register(new BadFaithServiceProvider());
        $app['dispatcher']->addSubscriber(new PhraseaLocaleSubscriber($app));

        $app->get('/', function (Application $app) {
            return $app['locale'];
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertRegExp('/[a-z]{2}_[A-Z]{2}/', $client->getResponse()->getContent());
    }

    public function testWithCookie()
    {
        $app = new Application();

        $app->register(new BadFaithServiceProvider());
        $app['dispatcher']->addSubscriber(new PhraseaLocaleSubscriber($app));

        $app->get('/', function (Application $app) {
            return $app['locale'];
        });

        $cookieJar = new CookieJar();
        $cookieJar->set(new BrowserCookie('locale', 'de_DE'));

        $client = new Client($app, array(), null, $cookieJar);
        $client->request('GET', '/');

        $this->assertEquals('de_DE', $client->getResponse()->getContent());
    }

    public function testWithHeaders()
    {
        $app = new Application();

        $app->register(new BadFaithServiceProvider());
        $app['dispatcher']->addSubscriber(new PhraseaLocaleSubscriber($app));

        $app->get('/', function (Application $app) {
            return $app['locale'];
        });

        $client = new Client($app);
        $client->request('GET', '/', array(), array(), array('HTTP_ACCEPT_LANGUAGE' => 'fr_FR,fr;q=0.9'));

        $this->assertEquals('fr_FR', $client->getResponse()->getContent());
    }

    public function testWithHeadersUsingMinus()
    {
        $app = new Application();

        $app->register(new BadFaithServiceProvider());
        $app['dispatcher']->addSubscriber(new PhraseaLocaleSubscriber($app));

        $app->get('/', function (Application $app) {
            return $app['locale'];
        });

        $client = new Client($app);
        $client->request('GET', '/', array(), array(), array('HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9'));

        $this->assertEquals('fr_FR', $client->getResponse()->getContent());
    }
}
