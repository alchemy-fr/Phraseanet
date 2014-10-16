<?php

namespace Alchemy\Tests\Phrasea\Setup;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Setup\Installer;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Alchemy\Phrasea\Core\Configuration\Compiler;

class InstallerTest extends \PhraseanetTestCase
{
    public function tearDown()
    {
        $app = new Application(Application::ENV_TEST);
        \phrasea::reset_sbasDatas($app['phraseanet.appbox']);
        \phrasea::reset_baseDatas($app['phraseanet.appbox']);
        parent::tearDown();
    }

    public function testInstall()
    {
        $app = new Application(Application::ENV_TEST);
        \phrasea::reset_sbasDatas($app['phraseanet.appbox']);
        \phrasea::reset_baseDatas($app['phraseanet.appbox']);

        $app->bindRoutes();

        $parser = new Parser();
        $config = $parser->parse(file_get_contents(__DIR__ . '/../../../../../config/configuration.yml'));
        $credentials = $config['main']['database'];

        $configFile = __DIR__ . '/configuration.yml';
        $compiledFile = __DIR__ . '/configuration.yml.php';

        @unlink($configFile);
        @unlink($compiledFile);

        $app['configuration.store'] = $app->share(function() use ($configFile, $compiledFile) {
            return new Configuration(new Yaml(), new Compiler(), $configFile, $compiledFile, true);
        });

        $app['conf'] = $app->share(function() use($app) {
            return new PropertyAccess($app['configuration.store']);
        });

        $app['phraseanet.appbox'] = $app->share(function() use($app) {
            return new \appbox($app);
        });

        $abInfo = [
            'host'     => 'localhost',
            'port'     => 3306,
            'user'     => $credentials['user'],
            'password' => $credentials['password'],
            'dbname'   => 'ab_setup_test',
        ];

        $abConn = $app['dbal.provider']($abInfo);
        $dbConn = $app['dbal.provider']([
            'host'     => 'localhost',
            'port'     => 3306,
            'user'     => $credentials['user'],
            'password' => $credentials['password'],
            'dbname'   => 'db_setup_test',
        ]);
        $key = $app['orm.add']($abInfo);
        $app['orm.ems.default'] = $key;
        $dataPath = __DIR__ . '/../../../../../datas/';

        $installer = new Installer($app);
        $installer->install(uniqid('admin') . '@example.com', 'sdfsdsd', $abConn, 'http://local.phrasea.test.installer/', $dataPath, $dbConn, 'en');

        $this->assertTrue($app['configuration.store']->isSetup());
        $this->assertTrue($app['phraseanet.configuration-tester']->isUpToDate());

        $databox = current($app['phraseanet.appbox']->get_databoxes());
        $this->assertContains('<path>'.realpath($dataPath).'/db_setup_test/subdefs</path>', $databox->get_structure());

        $conf = $app['configuration.store']->getConfig();
        $this->assertArrayHasKey('main', $conf);
        $this->assertArrayHasKey('key', $conf['main']);
        $this->assertGreaterThan(10, strlen($conf['main']['key']));

        @unlink($configFile);
        @unlink($compiledFile);

        $app['connection.pool.manager']->closeAll();
    }
}
