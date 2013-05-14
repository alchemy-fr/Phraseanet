<?php

namespace Alchemy\Tests\Phrasea\Controller;

use Symfony\Component\Yaml\Yaml;

class SetupTest extends \Silex\WebTestCase
{
    protected $app;

    public function createApplication()
    {
        return $this->app;
    }

    public function setUp()
    {
        // set test environment
        $environment = 'test';
        $this->app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Root.php';

        $this->app['phraseanet.configuration-tester'] = $this->getMockBuilder('Alchemy\Phrasea\Setup\ConfigurationTester')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testRouteSlash()
    {
        $this->app['phraseanet.configuration-tester']->expects($this->once())
            ->method('isBlank')
            ->will($this->returnValue(true));

        $client = $this->createClient();
        $crawler = $client->request('GET', '/setup/');
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/setup/installer/', $response->headers->get('location'));
    }

    public function testRouteSlashWhenInstalled()
    {
        $this->app['phraseanet.configuration-tester']->expects($this->once())
            ->method('isBlank')
            ->will($this->returnValue(false));

        $client = $this->createClient();
        $crawler = $client->request('GET', '/setup/');
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    public function testRouteSetupInstaller()
    {
        $client = $this->createClient();

        $this->app['phraseanet.configuration-tester']->expects($this->once())
            ->method('isBlank')
            ->will($this->returnValue(true));

        $crawler = $client->request('GET', '/setup/installer/');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRouteSetupInstallerStep2()
    {
        $client = $this->createClient();

        $this->app['phraseanet.configuration-tester']->expects($this->once())
            ->method('isBlank')
            ->will($this->returnValue(true));

        $crawler = $client->request('GET', '/setup/installer/step2/');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRouteSetupInstallerInstall()
    {
        $this->app['phraseanet.configuration-tester']->expects($this->once())
            ->method('isBlank')
            ->will($this->returnValue(true));

        $this->app['phraseanet.installer'] = $this->getMockBuilder('Alchemy\Phrasea\Setup\Installer')
            ->disableOriginalConstructor()
            ->getMock();

        $user = $this->getMockBuilder('User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->exactly(2))
            ->method('get_id')
            ->will($this->returnValue(4));

        $acl = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->once())
            ->method('get_granted_sbas')
            ->will($this->returnValue(array()));

        $user->expects($this->once())
            ->method('ACL')
            ->will($this->returnValue($acl));

        $this->app['phraseanet.installer']->expects($this->once())
            ->method('install')
            ->will($this->returnValue($user));

        $client = $this->createClient();
        $settings = Yaml::parse(file_get_contents(__DIR__ . '/../../../../../hudson/InstallDBs.yml'));
        $settings = $settings['database'];

        $host = isset($settings['host']) ? $settings['host'] : 'localhost';
        $port = isset($settings['port']) ? $settings['port'] : '3306';
        $MySQLuser = isset($settings['user']) ? $settings['user'] : 'root';
        $MySQLpassword = isset($settings['password']) ? $settings['password'] : '';
        $abName = isset($settings['applicationBox']) ? $settings['applicationBox'] : null;
        $dbName = isset($settings['dataBox']) ? $settings['dataBox'] : null;

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

        $crawler = $client->request('POST', '/setup/installer/install/', $params);
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(false === strpos($response->headers->get('location'), '/setup/installer/'));
    }
}
