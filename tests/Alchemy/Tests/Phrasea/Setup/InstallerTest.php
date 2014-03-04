<?php

namespace Alchemy\Tests\Phrasea\Setup;

use Alchemy\Phrasea\Setup\Installer;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Alchemy\Phrasea\Core\Configuration\Compiler;

class InstallerTest extends \PhraseanetTestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public static function tearDownAfterClass()
    {
        $app = new Application('test');
        \phrasea::reset_sbasDatas($app['phraseanet.appbox']);
        \phrasea::reset_baseDatas($app['phraseanet.appbox']);
        parent::tearDownAfterClass();
    }

    /**
     * @covers Alchemy\Phrasea\Setup\Installer
     */
    public function testInstall()
    {
        $this->dropDatabase();
        $app = new Application('test');
        $app->bindRoutes();

        $parser = new Parser();
        $connDatas = $parser->parse(file_get_contents(__DIR__ . '/../../../../../config/configuration.yml'));
        $credentials = $connDatas['main']['database'];

        $config = __DIR__ . '/configuration.yml';
        $compiled = __DIR__ . '/configuration.yml.php';

        @unlink($config);
        @unlink($compiled);

        $app['configuration.store'] = new Configuration(new Yaml(), new Compiler(), $config, $compiled, true);

        $abConn = self::$DI['app']['dbal.provider']->get([
            'host'     => 'localhost',
            'port'     => 3306,
            'user'     => $credentials['user'],
            'password' => $credentials['password'],
            'dbname'   => 'ab_unitTests',
        ]);
        $abConn->connect();

        $template = 'en';
        $dataPath = __DIR__ . '/../../../../../datas/';

        $installer = new Installer($app);
        $installer->install(uniqid('admin') . '@example.com', 'sdfsdsd', $abConn, 'http://local.phrasea.test.installer/', $dataPath, 'db_unitTests', $template);

        $this->assertTrue($app['configuration.store']->isSetup());
        $this->assertTrue($app['phraseanet.configuration-tester']->isUpToDate());

        $databoxes = $app['phraseanet.appbox']->get_databoxes();
        $databox = array_pop($databoxes);
        $this->assertContains('<path>'.realpath($dataPath).'/db_unitTests/subdefs</path>', $databox->get_structure());

        $conf = $app['configuration.store']->getConfig();
        $this->assertArrayHasKey('main', $conf);
        $this->assertArrayHasKey('key', $conf['main']);
        $this->assertGreaterThan(10, strlen($conf['main']['key']));

        @unlink($config);
        @unlink($compiled);
    }
}
