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

    public function testInstall()
    {
        $app = new Application('test');
        $app->bindRoutes();

        $parser = new Parser();
        $config = $parser->parse(file_get_contents(__DIR__ . '/../../../../../config/configuration.yml'));
        $credentials = $config['main']['database'];

        $configFile = __DIR__ . '/configuration.yml';
        $compiledFile = __DIR__ . '/configuration.yml.php';

        @unlink($configFile);
        @unlink($compiledFile);

        $app['configuration.store'] = new Configuration(new Yaml(), new Compiler(), $configFile, $compiledFile, true);

        $abConn = self::$DI['app']['dbal.provider']->get([
            'host'     => 'localhost',
            'port'     => 3306,
            'user'     => $credentials['user'],
            'password' => $credentials['password'],
            'dbname'   => 'ab_setup_test',
        ]);
        $abConn->connect();
        $dbConn = self::$DI['app']['dbal.provider']->get([
            'host'     => 'localhost',
            'port'     => 3306,
            'user'     => $credentials['user'],
            'password' => $credentials['password'],
            'dbname'   => 'db_setup_test',
        ]);
        $dbConn->connect();

        // empty databases
        $stmt = $abConn->prepare('DROP DATABASE ab_setup_test; CREATE DATABASE ab_setup_test');
        $stmt->execute();
        $stmt = $abConn->prepare('DROP DATABASE db_setup_test; CREATE DATABASE db_setup_test');
        $stmt->execute();
        unset($stmt);

        $dataPath = __DIR__ . '/../../../../../datas/';

        $installer = new Installer($app);
        $installer->install(uniqid('admin') . '@example.com', 'sdfsdsd', $abConn, 'http://local.phrasea.test.installer/', $dataPath, $dbConn, 'en');

        $this->assertTrue($app['configuration.store']->isSetup());
        $this->assertTrue($app['phraseanet.configuration-tester']->isUpToDate());

        $databoxes = $app['phraseanet.appbox']->get_databoxes();
        $databox = array_pop($databoxes);
        $this->assertContains('<path>'.realpath($dataPath).'/db_setup_test/subdefs</path>', $databox->get_structure());

        $conf = $app['configuration.store']->getConfig();
        $this->assertArrayHasKey('main', $conf);
        $this->assertArrayHasKey('key', $conf['main']);
        $this->assertGreaterThan(10, strlen($conf['main']['key']));

        @unlink($configFile);
        @unlink($compiledFile);
    }
}
