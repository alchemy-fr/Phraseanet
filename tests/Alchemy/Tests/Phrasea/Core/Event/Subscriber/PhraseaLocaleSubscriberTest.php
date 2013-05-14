<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Silex\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaLocaleSubscriber;
use Neutron\Silex\Provider\BadFaithServiceProvider;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Component\HttpFoundation\Request;

class PhraseaLocaleSubscriberTest extends \PhraseanetPHPUnitAbstract
{
    public function testBasic()
    {
        $app = $this->getAppThatReturnLocale();

        $this->mockRegistryAndReturnLocale($app, 'fr_FR');

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals('fr_FR', $client->getResponse()->getContent());
    }

    public function testWithCookie()
    {
        $app = $this->getAppThatReturnLocale();

        $cookieJar = new CookieJar();
        $cookieJar->set(new BrowserCookie('locale', 'de_DE'));

        $client = new Client($app, array(), null, $cookieJar);
        $client->request('GET', '/');

        $this->assertEquals('de_DE', $client->getResponse()->getContent());
    }

    public function testWithHeaders()
    {
        $app = $this->getAppThatReturnLocale();

        $client = new Client($app);
        $client->request('GET', '/', array(), array(), array('HTTP_ACCEPT_LANGUAGE' => 'fr_FR,fr;q=0.9'));

        $this->assertEquals('fr_FR', $client->getResponse()->getContent());
    }

    public function testWithHeadersUsingMinus()
    {
        $app = $this->getAppThatReturnLocale();

        $client = new Client($app);
        $client->request('GET', '/', array(), array(), array('HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9'));

        $this->assertEquals('fr_FR', $client->getResponse()->getContent());
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
        $app = new Application();
        $app['debug'] = true;

        $app->register(new BadFaithServiceProvider());
        $app['phraseanet.registry'] = $this->getMockBuilder('\registry')
            ->disableOriginalConstructor()
            ->getmock();

        $app['dispatcher']->addSubscriber(new PhraseaLocaleSubscriber($app));

        $app->get('/', function(Application $app, Request $request) {
            return $app['locale'] ? $app['locale'] : '';
        });

        return $app;
    }

    private function mockRegistryAndReturnLocale(Application $app, $locale)
    {
        $app['phraseanet.registry']->expects($this->any())
            ->method('get')
            ->will($this->returnValue($locale));
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
