<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAbstract.class.inc';

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

    public function setUp()
    {
        $this->markTestSkipped('To review');
        parent::setUp();
        $this->root = __DIR__ . '/../../../../';
        $this->temporaryUnInstall();
        $this->connection = self::$DI['app']['phraseanet.appbox']->get_connection();

        $this->registry = array();

        $params = array(
            'GV_base_datapath_noweb',
            'GV_ServerName',
            'php_binary',
            'convert_binary',
            'composite_binary',
            'swf_extract_binary',
            'pdf2swf_binary',
            'swf_render_binary',
            'unoconv_binary',
            'ffmpeg_binary',
            'mp4box_binary',
            'pdftotext_binary',
        );

        foreach ($params as $param) {
            $this->registry[$param] = self::$DI['app']['phraseanet.registry']->get($param);
        }
    }

    public function tearDown()
    {
        $this->temporaryReInstall();
        self::$DI['app']['phraseanet.appbox']->set_connection($this->connection);

        foreach ($this->registry as $param => $value) {
            self::$DI['app']['phraseanet.registry']->set($param, $value, \registry::TYPE_STRING);
        }

        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Setup\Installer::connect
     */
    public function testRouteSlash()
    {
        $crawler = self::$DI['client']->request('GET', '/');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/setup/installer/', $response->headers->get('location'));

        $this->temporaryReInstall();

        $crawler = self::$DI['client']->request('GET', '/');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Setup\Installer::connect
     */
    public function testRouteSetupInstaller()
    {
        $crawler = self::$DI['client']->request('GET', '/installer/');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/setup/installer/step2/', $response->headers->get('location'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Setup\Installer::connect
     */
    public function testRouteSetupInstallerStep2()
    {
        $crawler = self::$DI['client']->request('GET', '/installer/step2/');
        $response = self::$DI['client']->getResponse();
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


        $connection = new connection_pdo('unitTestsAB', $host, $port, $MySQLuser, $MySQLpassword, $abName, array(), false);

        self::$DI['app']['phraseanet.appbox']->set_connection($connection);

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

        $crawler = self::$DI['client']->request('POST', '/installer/install/', $params);
        $response = self::$DI['client']->getResponse();

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
