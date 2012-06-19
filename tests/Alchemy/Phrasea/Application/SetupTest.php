<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApplicationSetupTest extends PhraseanetWebTestCaseAbstract
{
    protected $client;
    protected $root;

    /**
     *
     * @var \appbox
     */
    protected $appbox;

    /**
     *
     * @var \connection_pdo
     */
    protected $connection;
    protected $registry = array();

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../lib/Alchemy/Phrasea/Application/Setup.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->root = __DIR__ . '/../../../../';
        $this->client = $this->createClient();
        $this->temporaryUnInstall();
        $this->appbox = appbox::get_instance(\bootstrap::getCore());
        $this->connection = $this->appbox->get_connection();

        $this->registry = array();

        $params = array(
            'GV_base_datapath_noweb',
            'GV_ServerName',
            'GV_cli',
            'GV_imagick',
            'GV_pathcomposite',
            'GV_swf_extract',
            'GV_pdf2swf',
            'GV_swf_render',
            'GV_unoconv',
            'GV_ffmpeg',
            'GV_mp4box',
            'GV_pdftotext',
        );

        $registry = $this->appbox->get_registry();

        foreach ($params as $param) {
            $this->registry[$param] = $registry->get($param);
        }
        $this->markTestSkipped('To review');
    }

    public function tearDown()
    {
        $this->temporaryReInstall();
        $this->appbox->set_connection($this->connection);

        $registry = $this->appbox->get_registry();

        foreach ($this->registry as $param => $value) {
            $registry->set($param, $value, \registry::TYPE_STRING);
        }

        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Setup\Installer::connect
     */
    public function testRouteSlash()
    {
        $crawler = $this->client->request('GET', '/');
        $response = $this->client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/setup/installer/', $response->headers->get('location'));

        $this->temporaryReInstall();

        $crawler = $this->client->request('GET', '/');
        $response = $this->client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Setup\Installer::connect
     */
    public function testRouteSetupInstaller()
    {
        $crawler = $this->client->request('GET', '/installer/');
        $response = $this->client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/setup/installer/step2/', $response->headers->get('location'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Setup\Installer::connect
     */
    public function testRouteSetupInstallerStep2()
    {
        $crawler = $this->client->request('GET', '/installer/step2/');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Setup\Installer::connect
     */
    public function testRouteSetupInstallerInstall()
    {

        $settings = Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->root . 'hudson/InstallDBs.yml'));

        $settings = $settings['database'];

        $host = isset($settings['host']) ? $settings['host'] : 'localhost';
        $port = isset($settings['port']) ? $settings['port'] : '3306';
        $MySQLuser = isset($settings['user']) ? $settings['user'] : 'root';
        $MySQLpassword = isset($settings['password']) ? $settings['password'] : '';
        $abName = isset($settings['applicationBox']) ? $settings['applicationBox'] : null;
        $dbName = isset($settings['dataBox']) ? $settings['dataBox'] : null;


        $connection = new connection_pdo('unitTestsAB', $host, $port, $MySQLuser, $MySQLpassword, $abName);

        $this->appbox->set_connection($connection);

        $dataDir = sys_get_temp_dir() . '/datainstall/';

        $params = array(
            'email'             => 'user@example.org',
            'password'          => 'prètty%%password',
            'binary_xpdf'       => '/path/to/xpdf',
            'binary_mplayer'    => '/path/to/mplayer',
            'binary_MP4Box'     => '/path/to/MP4Box',
            'binary_ffmpeg'     => '/path/to/ffmpeg',
            'binary_unoconv'    => '/path/to/unoconv',
            'binary_swfrender'  => '/path/to/swfrender',
            'binary_pdf2swf'    => '/path/to/pdf2swf',
            'binary_swfextract' => '/path/to/swfextract',
            'binary_exiftool'   => '/path/to/exiftool',
            'binary_composite'  => '/path/to/composite',
            'binary_convert'    => '/path/to/convert',
            'binary_php'        => '/path/to/php',
            'datapath_noweb'    => $dataDir . 'noweb',
            'ab_hostname'       => $host,
            'ab_port'           => $port,
            'ab_user'           => $MySQLuser,
            'ab_password'       => $MySQLpassword,
            'ab_name'           => $abName,
            'db_name'           => $dbName,
            'db_template'       => 'en-simple',
            'create_task'       => array(),
            'binary_phraseanet_indexer' => '/path/to/phraseanet_indexer',
        );

        $crawler = $this->client->request('POST', '/installer/install/', $params);
        $response = $this->client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(false === strpos($response->headers->get('location'), '/setup/installer/'));
    }

    public function temporaryUnInstall()
    {
        if (file_exists($this->root . 'config/config.yml')) {
            rename($this->root . 'config/config.yml', $this->root . 'config/config.yml.unitTests');
        }
        if (file_exists($this->root . 'config/services.yml')) {
            rename($this->root . 'config/services.yml', $this->root . 'config/services.yml.unitTests');
        }
        if (file_exists($this->root . 'config/connexions.yml')) {
            rename($this->root . 'config/connexions.yml', $this->root . 'config/connexions.yml.unitTests');
        }
    }

    public function temporaryReInstall()
    {
        if (file_exists($this->root . 'config/config.yml.unitTests')) {
            if (file_exists($this->root . 'config/config.yml')) {
                unlink($this->root . 'config/config.yml');
            }

            rename($this->root . 'config/config.yml.unitTests', $this->root . 'config/config.yml');
        }
        if (file_exists($this->root . 'config/services.yml.unitTests')) {
            if (file_exists($this->root . 'config/services.yml')) {
                unlink($this->root . 'config/services.yml');
            }

            rename($this->root . 'config/services.yml.unitTests', $this->root . 'config/services.yml');
        }
        if (file_exists($this->root . 'config/connexions.yml.unitTests')) {
            if (file_exists($this->root . 'config/connexions.yml')) {
                unlink($this->root . 'config/connexions.yml');
            }

            rename($this->root . 'config/connexions.yml.unitTests', $this->root . 'config/connexions.yml');
        }
    }
}
