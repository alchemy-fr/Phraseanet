<?php

namespace Alchemy\Tests\Phrasea;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Component\Routing\Generator\UrlGenerator;

class ApplicationTest extends \PhraseanetPHPUnitAbstract
{
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

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testFlashSession()
    {
        $app = new Application('test');
        $sessionId = null;
        $app->post('/prod/upload/', function(Application $app) use (&$sessionId) {
            $sessionId = $app['session']->getId();
        });

        $client = new Client($app);

        $client->request('POST', '/prod/upload/', array('php_session_id'=>'123456'), array(), array('HTTP_USER_AGENT'=>'flash'));
        $this->assertEquals('123456', $sessionId);
    }

    public function testWebProfilerDisableByDefault()
    {
        $app = new Application('prod');
        $this->assertFalse(isset($app['profiler']));

        $app = new Application('test');
        $this->assertFalse(isset($app['profiler']));
    }

    public function testWebProfilerEnableInDevMode()
    {
        $app = new Application('dev');
        $this->assertTrue(isset($app['profiler']));
    }

    public function testGeneratePath()
    {
        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $app = new Application();
        $app['url_generator'] = $generator;

        $ret = 'retval-' . mt_rand();
        $route = 'route';

        $generator->expects($this->once())
            ->method('generate')
            ->with($this->equalTo($route), $this->equalTo(array()), $this->equalTo(UrlGenerator::ABSOLUTE_PATH))
            ->will($this->returnValue($ret));

        $this->assertEquals($ret, $app->path($route));
    }

    public function testGenerateUrl()
    {
        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $app = new Application();
        $app['url_generator'] = $generator;

        $ret = 'retval-' . mt_rand();
        $route = 'route';

        $generator->expects($this->once())
            ->method('generate')
            ->with($this->equalTo($route), $this->equalTo(array()), $this->equalTo(UrlGenerator::ABSOLUTE_URL))
            ->will($this->returnValue($ret));

        $this->assertEquals($ret, $app->url($route));
    }

    public function testCreateForm()
    {
        $factory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $app = new Application();
        $app['form.factory'] = $factory;

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $type = $this->getMock('Symfony\Component\Form\FormTypeInterface');
        $data = array('some' => 'data');
        $options = array();

        $parent = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $factory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($type), $this->equalTo($data), $this->equalTo($options), $this->equalTo($parent))
            ->will($this->returnValue($form));

        $this->assertEquals($form, $app->form($type, $data, $options, $parent));
    }

    public function testAddSetFlash()
    {
        $app = new Application('test');

        $this->assertEquals(array(), $app->getFlash('info'));
        $this->assertEquals(array('BOUM'), $app->getFlash('info', array('BOUM')));

        $app->addFlash('success', 'BAMBA');
        $this->assertEquals(array('BAMBA'), $app->getFlash('success'));
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testAddSetFlashWithInvalidArgument()
    {
        $app = new Application('test');

        $app->addFlash('caution', 'BAMBA');
    }

    public function testAddCaptcha()
    {
        $app = new Application('test');
        $app['phraseanet.registry'] = $this->getMock('registryInterface');
        $app['phraseanet.registry']
            ->expects($this->any())
            ->method('get')
            ->with('GV_captchas')
            ->will($this->returnValue(true));

        $this->assertFalse($app->isCaptchaRequired());
        $app->requireCaptcha();
        $this->assertTrue($app->isCaptchaRequired());
        $this->assertFalse($app->isCaptchaRequired());
    }

    public function testAddUnlockLinkToUsrId()
    {
        $app = new Application('test');

        $this->assertNull($app->getUnlockAccountData());
        $app->addUnlockAccountData(42);
        $this->assertEquals(42, $app->getUnlockAccountData());
        $this->assertNull($app->getUnlockAccountData());
    }

    public function testRootPath()
    {
        $app = new Application('test');

        $this->assertFileExists($app['root.path'].'/LICENSE');
        $this->assertFileExists($app['root.path'].'/README.md');
        $this->assertFileExists($app['root.path'].'/lib');
        $this->assertFileExists($app['root.path'].'/www');
    }

    public function testUrlGeneratorContext()
    {
        $app = new Application('test');
        $app['phraseanet.configuration'] = $this->getMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');
        $app['phraseanet.configuration']->expects($this->once())
            ->method('offsetGet')
            ->with('main')
            ->will($this->returnValue(array('servername' => 'https://cat.turbocat.com/')));

        $this->assertEquals('https', $app['url_generator']->getContext()->getScheme());
        $this->assertEquals('cat.turbocat.com', $app['url_generator']->getContext()->getHost());
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

    private function getClientWithCookie(Application $app, $locale = null)
    {
        $cookieJar = new CookieJar();
        if (null !== $locale) {
            $cookieJar->set(new BrowserCookie('locale', $locale));
        }

        return new Client($app, array(), null, $cookieJar);
    }
}
