<?php

namespace Alchemy\Tests\Phrasea\Setup;

use Alchemy\Phrasea\Setup\Installer;
use Alchemy\Phrasea\Application;
use Alchemy\Tests\Phrasea\Setup\TestSpecifications;
use Alchemy\Phrasea\Core\Configuration;
use Symfony\Component\Yaml\Parser;

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

        $parser = new Parser();
        $connDatas = $parser->parse(file_get_contents(__DIR__ . '/../../../../../config/connexions.yml'));
        $credentials = $connDatas['main_connexion'];

        $specifications = new TestSpecifications();
        $app['phraseanet.configuration'] = new Configuration($specifications);

        $abConn = new \connection_pdo('abConn', 'localhost', 3306, $credentials['user'], $credentials['password'], 'ab_unitTests');
        $dbConn = new \connection_pdo('dbConn', 'localhost', 3306, $credentials['user'], $credentials['password'], 'db_unitTests');

        $template = 'en';
        $dataPath = __DIR__ . '/../../../../../datas/';

        $installer = new Installer($app, 'admin@example.com', 'sdfsdsd', $abConn, 'http://local.phrasea.test.installer/', $dataPath, $dbConn, $template);
        $installer->install();

        \User_Adapter::unsetInstances();

        $this->assertTrue($specifications->isSetup());
        $this->assertTrue($app['phraseanet.configuration-tester']->isUpToDate());
        $confs = $app['phraseanet.configuration']->getConfigurations();
        $this->assertArrayHasKey('key', $confs);
        $this->assertGreaterThan(10, strlen($confs['key']));
    }
}
