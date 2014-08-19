<?php

namespace Alchemy\Tests\Phrasea\Controller;

use Symfony\Component\Yaml\Yaml;

class SetupTest extends \PhraseanetWebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app = $this->loadApp('lib/Alchemy/Phrasea/Application/Root.php');

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
        $this->app['phraseanet.configuration-tester']->expects($this->any())
            ->method('isInstalled')
            ->will($this->returnValue(true));
        $this->app['phraseanet.configuration-tester']->expects($this->any())
            ->method('isBlank')
            ->will($this->returnValue(false));

        $client = $this->createClient();
        $client->request('GET', '/setup/');
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login/', $response->headers->get('location'));
    }

    public function testRouteInstructionsWhenUpgradeRequired()
    {
        $this->app['phraseanet.configuration-tester']->expects($this->any())
            ->method('isInstalled')
            ->will($this->returnValue(false));
        $this->app['phraseanet.configuration-tester']->expects($this->any())
            ->method('isBlank')
            ->will($this->returnValue(false));

        $client = $this->createClient();
        $client->request('GET', '/setup/');
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/setup/upgrade-instructions/', $response->headers->get('location'));
    }

    public function testRouteSetupInstaller()
    {
        $client = $this->createClient();

        $this->app['phraseanet.configuration-tester']->expects($this->any())
            ->method('isBlank')
            ->will($this->returnValue(true));

        $client->request('GET', '/setup/installer/');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRouteSetupInstallerStep2()
    {
        $client = $this->createClient();

        $this->app['phraseanet.configuration-tester']->expects($this->any())
            ->method('isBlank')
            ->will($this->returnValue(true));

        $client->request('GET', '/setup/installer/step2/');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRouteSetupInstallerInstall()
    {
        $emMock  = $this->createEntityManagerMock();
        $this->app['repo.sessions'] = $this->createEntityRepositoryMock();
        $this->app['EM'] = $emMock;

        $this->app['phraseanet.configuration-tester']->expects($this->once())
            ->method('isBlank')
            ->will($this->returnValue(true));

        $this->app['phraseanet.installer'] = $this->getMockBuilder('Alchemy\Phrasea\Setup\Installer')
            ->disableOriginalConstructor()
            ->getMock();

        $user = $this->createUserMock();
        $user->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(self::$DI['user']->getId()));

        $this->app['phraseanet.installer']->expects($this->once())
            ->method('install')
            ->will($this->returnValue($user));
        
        $authenticator = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Authenticator')
            ->disableOriginalConstructor()
            ->getMock();
        $session = $this->getMock('Entities\Session');

        $authenticator->expects($this->once())
            ->method('openAccount')
            ->with($this->equalTo($user))
            ->will($this->returnValue($session));

        $this->app['authentication'] = $authenticator;

        $client = $this->createClient();
        $settings = Yaml::parse(file_get_contents(__DIR__ . '/../../../../../hudson/InstallDBs.yml'));
        $settings = $settings['database'];

        $host = isset($settings['host']) ? $settings['host'] : 'localhost';
        $port = isset($settings['port']) ? $settings['port'] : '3306';
        $user = isset($settings['user']) ? $settings['user'] : 'root';
        $password = isset($settings['password']) ? $settings['password'] : '';
        $abName = isset($settings['ab_name']) ? $settings['ab_name'] : null;
        $dbName = isset($settings['db_name']) ? $settings['db_name'] : null;

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
            'datapath_noweb'    => sys_get_temp_dir() . '/datainstall/noweb',
            'ab_hostname'       => $host,
            'ab_port'           => $port,
            'ab_user'           => $user,
            'ab_password'       => $password,
            'ab_name'           => $abName,
            'db_name'           => $dbName,
            'db_template'       => 'en',
            'create_task'       => array(),
            'binary_phraseanet_indexer' => '/path/to/phraseanet_indexer',
        );

        $client->request('POST', '/setup/installer/install/', $params);
        $response = $client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(false === strpos($response->headers->get('location'), '/setup/installer/'), $response);
    }

    public function testSetupProvidesPathTest()
    {
        $this->app['phraseanet.configuration-tester']->expects($this->once())
            ->method('isBlank')
            ->will($this->returnValue(true));

        $client = $this->createClient();
        $client->request('GET', '/setup/test/path/?path=/usr/bin/php');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
    }

    public function testSetupProvidesConnectionTest()
    {
        $this->app['phraseanet.configuration-tester']->expects($this->once())
            ->method('isBlank')
            ->will($this->returnValue(true));

        $client = $this->createClient();
        $client->request('GET', '/setup/connection_test/mysql/?user=admin&password=secret&dbname=phraseanet');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));
    }
}
