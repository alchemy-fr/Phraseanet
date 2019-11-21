<?php

namespace Alchemy\Tests\Phrasea;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\HostConfiguration;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * @group functional
 * @group legacy
 */
class ApplicationTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testDebug()
    {
        $app = new Application('prod');
        $this->assertFalse($app['debug']);

        $app = new Application('test');
        $this->assertFalse($app['debug']);

        $app = new Application('dev');
        $this->assertTrue($app['debug']);
    }

    public function testExceptionHandlerIsNotYetInstantiated()
    {
        $app = new Application(Application::ENV_TEST);
        $app['exception_handler'] = new TestExceptionHandlerSubscriber();

        $app->get('/', function () {
            throw new \Exception();
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals('GOT IT !', $client->getResponse()->getContent());
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

        $this->assertEquals(3, count($cookies['']['/']));
    }

    /**
     * @dataProvider provideDisabledRoutes
     */
    public function testCookieDisabledOnSomeRoutes($disabled, $route)
    {
        $app = $this->getApp();
        $app->get($route, function () {
           $response = new Response();
           $response->headers->setCookie(new Cookie('key', 'value'));

           return $response;
        });

        $client = new Client($app);
        $client->request('GET', $route);

        if ($disabled) {
            $this->assertCount(0, $client->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY));
        } else {
            $this->assertGreaterThanOrEqual(1, count($client->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY)));
        }
    }

    public function provideDisabledRoutes()
    {
        return [
            [true, '/api/v1/'],
            [false, '/'],
        ];
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testCookieLocale()
    {
        foreach (array_keys(Application::getAvailableLanguages()) as $locale) {
            $client = $this->getClientWithCookie($this->getAppThatReturnLocale(), $locale);
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
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT_LANGUAGE' => '']);

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
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT_LANGUAGE' => '']);

        $this->assertEquals('en_USA', $client->getResponse()->getContent());
    }

    /**
     * @covers Alchemy\Phrasea\Application
     */
    public function testFlashSession()
    {
        $app = new Application(Application::ENV_TEST);
        $sessionId = null;
        $app->post('/prod/upload/', function (Application $app) use (&$sessionId) {
            $sessionId = $app['session']->getId();
            return "";
        });

        $client = new Client($app);

        $client->request('POST', '/prod/upload/', ['php_session_id'=>'123456'], [], ['HTTP_USER_AGENT'=>'flash']);
        $this->assertEquals('123456', $sessionId);
    }

    public function testGeneratePath()
    {
        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $app = new Application(Application::ENV_TEST);
        $app['url_generator'] = $generator;

        $ret = 'retval-' . mt_rand();
        $route = 'route';

        $generator->expects($this->once())
            ->method('generate')
            ->with($this->equalTo($route), $this->equalTo([]), $this->equalTo(UrlGenerator::ABSOLUTE_PATH))
            ->will($this->returnValue($ret));

        $this->assertEquals($ret, $app->path($route));
    }

    public function testGenerateUrl()
    {
        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $app = new Application(Application::ENV_TEST);
        $app['url_generator'] = $generator;

        $ret = 'retval-' . mt_rand();
        $route = 'route';

        $generator->expects($this->once())
            ->method('generate')
            ->with($this->equalTo($route), $this->equalTo([]), $this->equalTo(UrlGenerator::ABSOLUTE_URL))
            ->will($this->returnValue($ret));

        $this->assertEquals($ret, $app->url($route));
    }

    public function testCreateForm()
    {
        $factory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $app = new Application(Application::ENV_TEST);
        $app['form.factory'] = $factory;

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $type = $this->getMock('Symfony\Component\Form\FormTypeInterface');
        $data = ['some' => 'data'];
        $options = [];

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
        $app = new Application(Application::ENV_TEST);

        $this->assertEquals([], $app->getFlash('info'));
        $this->assertEquals(['BOUM'], $app->getFlash('info', ['BOUM']));

        $app->addFlash('success', 'BAMBA');
        $this->assertEquals(['BAMBA'], $app->getFlash('success'));
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testAddSetFlashWithInvalidArgument()
    {
        $app = new Application(Application::ENV_TEST);

        $app->addFlash('caution', 'BAMBA');
    }

    public function testAddCaptcha()
    {
        $app = new Application(Application::ENV_TEST);
        $app['conf'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\PropertyAccess')
            ->disableOriginalConstructor()
            ->getMock();
        $app['conf']
            ->expects($this->any())
            ->method('get')
            ->with(['registry', 'webservices', 'captcha-enabled'])
            ->will($this->returnValue(true));

        $this->assertFalse($app->isCaptchaRequired());
        $app->requireCaptcha();
        $this->assertTrue($app->isCaptchaRequired());
        $this->assertFalse($app->isCaptchaRequired());
    }

    public function testAddUnlockLinkToUsrId()
    {
        $app = new Application(Application::ENV_TEST);

        $this->assertNull($app->getUnlockAccountData());
        $app->addUnlockAccountData(42);
        $this->assertEquals(42, $app->getUnlockAccountData());
        $this->assertNull($app->getUnlockAccountData());
    }

    public function testRootPath()
    {
        $app = new Application(Application::ENV_TEST);

        $this->assertFileExists($app['root.path'].'/LICENSE');
        $this->assertFileExists($app['root.path'].'/README.md');
        $this->assertFileExists($app['root.path'].'/lib');
        $this->assertFileExists($app['root.path'].'/www');
    }

    public function testUrlGeneratorContext()
    {
        $app = new Application(Application::ENV_TEST);
        $app['conf'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\PropertyAccess')
            ->disableOriginalConstructor()
            ->getMock();
        $app['conf']->expects($this->once())
            ->method('get')
            ->with('servername')
            ->will($this->returnValue('https://cat.turbocat.com/'));

        $this->assertEquals('https', $app['url_generator']->getContext()->getScheme());
        $this->assertEquals('cat.turbocat.com', $app['url_generator']->getContext()->getHost());
    }

    public function testMaintenanceModeTriggers503s()
    {
        $app = new Application(Application::ENV_TEST);

        $app['phraseanet.configuration.config-path'] = __DIR__ . '/Core/Event/Subscriber/Fixtures/configuration-maintenance.yml';
        $app['phraseanet.configuration.config-compiled-path'] = __DIR__ . '/Core/Event/Subscriber/Fixtures/configuration-maintenance.php';

        if (is_file($app['phraseanet.configuration.config-compiled-path'])) {
            unlink($app['phraseanet.configuration.config-compiled-path']);
        }

        $app['configuration.store'] = new HostConfiguration(new Configuration(
            $app['phraseanet.configuration.yaml-parser'],
            $app['phraseanet.configuration.compiler'],
            $app['phraseanet.configuration.config-path'],
            $app['phraseanet.configuration.config-compiled-path'],
            $app['debug']
        ));

        $app['conf'] = new PropertyAccess($app['configuration.store']);

        $app->get('/', function (Application $app, Request $request) {
            return 'Hello';
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals(503, $client->getResponse()->getStatusCode());
        $this->assertNotEquals('Hello', $client->getResponse()->getContent());

        if (is_file($app['phraseanet.configuration.config-compiled-path'])) {
            unlink($app['phraseanet.configuration.config-compiled-path']);
        }
    }

    public function testThatMediaAlachemystIsRegistered()
    {
        $app = new Application(Application::ENV_TEST);

        $this->assertSame($app['monolog'], $app['media-alchemyst.logger']);
        $this->assertInstanceOf('MediaAlchemyst\Alchemyst', $app['media-alchemyst']);
    }

    /**
     * @dataProvider transProvider
     */
    public function testCachedTranslator($key, $locale, $expected)
    {
        $tempDir = sys_get_temp_dir() . '/temp-trans';
        $this->cleanupTempDir($tempDir);

        $app = $this->getPreparedApp($tempDir);

        $this->assertInstanceOf('Alchemy\Phrasea\Utilities\CachedTranslator', $app['translator']);

        $result = $app['translator']->trans($key, [], null, $locale);

        $this->assertEquals($expected, $result);
        $this->assertFileExists($tempDir.'/catalogue.'.($locale ?: 'en').'.php');
    }

    public function transProvider()
    {
        return [
            ['key1', 'de', 'The german translation'],
            ['test.key', 'de', 'It works in german'],
        ];
    }

    private function getPreparedApp($tempDir)
    {
        $app = new Application(Application::ENV_TEST);
        $app['translator.cache-options'] = [
            'debug' => false,
            'cache_dir' => $tempDir,
        ];

        $app['translator.domains'] = [
            'messages' => [
                'en' => [
                    'key1' => 'The translation',
                    'key_only_english' => 'Foo',
                    'key2' => 'One apple|%count% apples',
                    'test' => [
                        'key' => 'It works'
                    ]
                ],
                'de' => [
                    'key1' => 'The german translation',
                    'key2' => 'One german apple|%count% german apples',
                    'test' => [
                        'key' => 'It works in german'
                    ]
                ]
            ]
        ];

        return $app;
    }

    private function cleanupTempDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (new \DirectoryIterator($dir) as $fileinfo) {
            if ($fileinfo->isFile()) {
                unlink($fileinfo->getPathname());
            }
        }
    }

    private function getAppThatReturnLocale()
    {
        $app = new Application(Application::ENV_TEST);

        $app->get('/', function (Application $app, Request $request) {
            return $app['locale'];
        });
        unset($app['exception_handler']);

        return $app;
    }

    private function mockRegistryAndReturnLocale(Application $app, $locale)
    {
        $database =  $app['conf']->get(['main', 'database']);
        $app['conf'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\PropertyAccess')
            ->disableOriginalConstructor()
            ->getmock();
        $app['conf']->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($param, $default) use ($locale, $database) {

                switch ($param) {
                    case ['languages', 'default']:
                        return $locale;
                    case ['languages', 'available']:
                        return [];
                    case ['border-manager', 'extension-mapping']:
                        return [];
                    case ['main', 'maintenance']:
                        return false;
                    case ['main', 'search-engine', 'type']:
                        return 'elasticsearch';
                    case ['main', 'search-engine', 'options']:
                        return [];
                    case ['main', 'database']:
                        return $database;
                }

                return $default;
            }));
    }

    private function getApp()
    {
        $app = new Application(Application::ENV_TEST);
        $app->get('/', function (Application $app, Request $request) {

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

        return new Client($app, [], null, $cookieJar);
    }
}

class TestExceptionHandlerSubscriber implements EventSubscriberInterface
{
    public function onSilexError(GetResponseForExceptionEvent $event)
    {
        $event->setResponse(new Response('GOT IT !'));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::EXCEPTION => ['onSilexError', 0]];
    }
}
