<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Core\Provider\LocaleServiceProvider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\LocaleServiceProvider
 */
class LocaleServiceProvidertest extends \PhraseanetTestCase
{
    public function testLocalesAvailable()
    {
        $app = $this->loadApp();
        $app->register(new LocaleServiceProvider());

        $this->assertEquals(Application::getAvailableLanguages(), $app['locales.available']);
    }

    public function testLocalesAvailableCustomized()
    {
        $app = $this->loadApp();
        $app->register(new LocaleServiceProvider());
        $app['root.path'] = __DIR__ . '/../../../../../..';
        $app->register(new ConfigurationServiceProvider());
        $app['conf']->set(['main', 'languages'], ['fr', 'zh', 'de']);

        $original = Application::getAvailableLanguages();
        unset($original['en']);
        unset($original['nl']);

        $this->assertEquals($original, $app['locales.available']);
    }

    public function testLocalesCustomizedWithError()
    {
        $app = $this->loadApp();
        $app->register(new LocaleServiceProvider());
        $app['root.path'] = __DIR__ . '/../../../../../..';
        $app->register(new ConfigurationServiceProvider());

        $app['conf']->set(['main', 'languages'], ['en_US']);

        $app['monolog'] = $this->getMock('Psr\Log\LoggerInterface');
        $app['monolog']->expects($this->once())
            ->method('error');

        $original = Application::getAvailableLanguages();

        $this->assertEquals($original, $app['locales.available']);
    }

    public function testLocaleBeforeBoot()
    {
        $app = $this->loadApp();
        $app->register(new LocaleServiceProvider());
        $app['phraseanet.registry'] = $this->getMockBuilder('registry')
            ->disableOriginalConstructor()
            ->getMock();
        $app['phraseanet.registry']->expects($this->once())
            ->method('get')
            ->with('GV_default_lng', 'en')
            ->will($this->returnValue('fr'));

        $this->assertEquals('fr', $app['locale']);
    }
}
