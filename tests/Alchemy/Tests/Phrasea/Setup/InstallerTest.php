<?php

namespace Alchemy\Tests\Phrasea\Setup;

use Alchemy\Phrasea\Setup\Installer;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Alchemy\Phrasea\Core\Configuration\Compiler;

class InstallerTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        parent::setUp();
        \connection::close_connections();
    }

    public function tearDown()
    {
        \connection::close_connections();
        parent::tearDown();
    }

    public static function tearDownAfterClass()
    {
        $app = new Application('test');
        \connection::close_connections();
        \phrasea::reset_sbasDatas($app['phraseanet.appbox']);
        \phrasea::reset_baseDatas($app['phraseanet.appbox']);
        parent::tearDownAfterClass();
    }

    /**
     * @covers Alchemy\Phrasea\Setup\Installer
     */
    public function testInstall()
    {
        $app = new Application('test');
        $app->bindRoutes();

        $parser = new Parser();
        $connDatas = $parser->parse(file_get_contents(__DIR__ . '/../../../../../config/configuration.yml'));
        $credentials = $connDatas['main']['database'];

        $config = __DIR__ . '/configuration.yml';
        $compiled = __DIR__ . '/configuration.yml.php';

        @unlink($config);
        @unlink($compiled);

        $app['phraseanet.configuration'] = new Configuration(new Yaml(), new Compiler(), $config, $compiled, true);

        $abConn = new \connection_pdo('abConn', 'localhost', 3306, $credentials['user'], $credentials['password'], 'ab_unitTests');
        $dbConn = new \connection_pdo('dbConn', 'localhost', 3306, $credentials['user'], $credentials['password'], 'db_unitTests');

        $template = 'en';
        $dataPath = __DIR__ . '/../../../../../datas/';

        $installer = new Installer($app);
        $installer->install('admin@example.com', 'sdfsdsd', $abConn, 'http://local.phrasea.test.installer/', $dataPath, $dbConn, $template);

        \User_Adapter::unsetInstances();

        $this->assertTrue($app['phraseanet.configuration']->isSetup());
        $this->assertTrue($app['phraseanet.configuration-tester']->isUpToDate());
        $conf = $app['phraseanet.configuration']->getConfig();
        $this->assertArrayHasKey('main', $conf);
        $this->assertArrayHasKey('key', $conf['main']);
        $this->assertGreaterThan(10, strlen($conf['main']['key']));

        @unlink($config);
        @unlink($compiled);
    }
}
