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

        $app['phraseanet.configuration'] = new Configuration(new Yaml(), new Compiler(), $configFile, $compiledFile, true);

        $conn = new \connection_pdo('conn', 'localhost', 3306, $credentials['user'], $credentials['password']);
        // empty databases
        $conn->exec('DROP DATABASE IF EXISTS `ab_unitTests`;');
        $conn->exec('CREATE DATABASE IF NOT EXISTS `ab_unitTests`;');
        $conn->exec('DROP DATABASE IF EXISTS `db_unitTests`;');
        $conn->exec('CREATE DATABASE IF NOT EXISTS `db_unitTests`;');

        unset($conn);

        $abConn = new \connection_pdo('abConn', 'localhost', 3306, $credentials['user'], $credentials['password'], 'ab_unitTests');
        $dbConn = new \connection_pdo('dbConn', 'localhost', 3306, $credentials['user'], $credentials['password'], 'db_unitTests');

        // empty databases
        $stmt = $abConn->prepare('DROP DATABASE ab_unitTests; CREATE DATABASE ab_unitTests');
        $stmt->execute();
        $stmt = $abConn->prepare('DROP DATABASE db_unitTests; CREATE DATABASE db_unitTests');
        $stmt->execute();
        unset($stmt);

        $dataPath = __DIR__ . '/../../../../../datas/';

        $installer = new Installer($app);
        $installer->install('admin@example.com', 'sdfsdsd', $abConn, 'http://local.phrasea.test.installer/', $dataPath, $dbConn, 'en');

        \User_Adapter::unsetInstances();

        $this->assertTrue($app['phraseanet.configuration']->isSetup());
        $this->assertTrue($app['phraseanet.configuration-tester']->isUpToDate());

        $databoxes = $app['phraseanet.appbox']->get_databoxes();
        $databox = array_pop($databoxes);
        $this->assertContains('<path>'.realpath($dataPath).'/db_unitTests/subdefs</path>', $databox->get_structure());

        $conf = $app['phraseanet.configuration']->getConfig();
        $this->assertArrayHasKey('main', $conf);
        $this->assertArrayHasKey('key', $conf['main']);
        $this->assertGreaterThan(10, strlen($conf['main']['key']));

        @unlink($configFile);
        @unlink($compiledFile);
    }
}
