<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Core\Provider\LocaleServiceProvider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\LocaleServiceProvider
 */
class LocaleServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testLocalesAvailable()
    {
        $app = new Application();
        $app->register(new LocaleServiceProvider());

        $this->assertEquals(Application::getAvailableLanguages(), $app['locales.available']);
    }

    public function testLocalesAvailableCustomized()
    {
        $app = new Application();
        $app->register(new LocaleServiceProvider());
        $app['root.path'] = __DIR__ . '/../../../../../..';
        $app->register(new ConfigurationServiceProvider());
        $app['conf']->set(['main', 'languages'], ['fr_FR', 'en_US', 'de']);

        $original = Application::getAvailableLanguages();
        unset($original['en_GB']);
        unset($original['nl_NL']);

        $this->assertEquals($original, $app['locales.available']);
    }

    public function testLocalesCustomizedWithError()
    {
        $app = new Application();
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

    public function testLocalesI18nAvailable()
    {
        $app = new Application();
        $app->register(new LocaleServiceProvider());

        $this->assertEquals(array_values(Application::getAvailableLanguages()), array_values($app['locales.I18n.available']));
        $this->assertEquals(['de', 'en', 'fr', 'nl'], array_keys($app['locales.I18n.available']));
    }

    public function testLocaleI18n()
    {
        $app = new Application();
        $app->register(new LocaleServiceProvider());
        $app['locale'] = 'de_CA';

        $this->assertEquals('de', $app['locale.I18n']);
    }

    public function testLocalel10n()
    {
        $app = new Application();
        $app->register(new LocaleServiceProvider());
        $app['locale'] = 'de_CA';

        $this->assertEquals('CA', $app['locale.l10n']);
    }

    public function testLocaleBeforeBoot()
    {
        $app = new Application();
        $app->register(new LocaleServiceProvider());
        $app['phraseanet.registry'] = $this->getMockBuilder('registry')
            ->disableOriginalConstructor()
            ->getMock();
        $app['phraseanet.registry']->expects($this->once())
            ->method('get')
            ->with('GV_default_lng', 'en_GB')
            ->will($this->returnValue('fr_FR'));

        $this->assertEquals('fr_FR', $app['locale']);
    }
}
