<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Silex\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaLocaleSubscriber;
use Alchemy\Phrasea\Core\Provider\LocaleServiceProvider;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Component\HttpFoundation\Request;

class PhraseaLocaleSubscriberTest extends \PhraseanetPHPUnitAbstract
{
    public function testBasic()
    {
        $app = $this->getAppThatReturnLocale();

        $this->mockRegistryAndReturnLocale($app, 'fr');

        $client = new Client($app);
        $client->request('GET', '/', [], [], ['HTTP_accept-language' => '']);

        $this->assertEquals('fr', $client->getResponse()->getContent());
    }

    public function testWithCookie()
    {
        $app = $this->getAppThatReturnLocale();

        $cookieJar = new CookieJar();
        $cookieJar->set(new BrowserCookie('locale', 'de'));

        $client = new Client($app, [], null, $cookieJar);
        $client->request('GET', '/', [], [], ['HTTP_accept-language' => '']);

        $this->assertEquals('de', $client->getResponse()->getContent());
    }

    public function testWithHeaders()
    {
        $app = $this->getAppThatReturnLocale();

        $client = new Client($app);
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT_LANGUAGE' => 'fr_FR,fr;q=0.9']);

        $this->assertEquals('fr', $client->getResponse()->getContent());
    }

    public function testWithHeadersUsingMinus()
    {
        $app = $this->getAppThatReturnLocale();

        $client = new Client($app);
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9']);

        $this->assertEquals('fr', $client->getResponse()->getContent());
    }

    public function testCookieIsSet()
    {
        $client = new Client(self::$DI['app']);
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9']);

        $settedCookie = null;
        foreach ($client->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'locale') {
                $settedCookie = $cookie;
                break;
            }
        }

        $this->assertNotNull($settedCookie);
        $this->assertEquals('fr', $settedCookie->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testNoCookieLocaleReturnsDefaultLocale()
    {
        $app = $this->getAppThatReturnLocale();
        $this->mockRegistryAndReturnLocale($app, 'en_USA');

        $client = $this->getClientWithCookie($app, null);
        $client->request('GET', '/', [], [], ['HTTP_accept-language' => '']);

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
        $client->request('GET', '/', [], [], ['HTTP_accept-language' => '']);

        $this->assertEquals('en_USA', $client->getResponse()->getContent());
    }

    private function getAppThatReturnLocale()
    {
        $app = new Application();
        $app['debug'] = true;
        $app->register(new LocaleServiceProvider());
        $app['phraseanet.registry'] = $this->getMockBuilder('\registry')
            ->disableOriginalConstructor()
            ->getmock();
        $app['configuration.store'] = $this->getMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');

        $app['dispatcher']->addSubscriber(new PhraseaLocaleSubscriber($app));

        $app->get('/', function (Application $app, Request $request) {
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

    private function getClientWithCookie(Application $app, $locale = 'fr')
    {
        $cookieJar = new CookieJar();
        if ($locale) {
            $cookieJar->set(new BrowserCookie('locale', $locale));
        }

        return new Client($app, [], null, $cookieJar);
    }
}
