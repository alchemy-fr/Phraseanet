<?php

namespace Alchemy\Tests\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Setup\Install;
use Symfony\Component\Yaml\Yaml;
use Alchemy\Phrasea\Core\Configuration\StructureTemplate;

/**
 * @group functional
 * @group legacy
 */
class InstallTest extends \PhraseanetTestCase
{
    private $bkp = null;

    public function setUp()
    {
        parent::setUp();
        $this->bkp = self::$DI['app']['conf']->get('main');
    }

    public function tearDown()
    {
        self::$DI['app']['conf']->set('main', $this->bkp);
        parent::tearDown();
    }


    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $email = 'jean@dupont.io';
        $password = 'sup4ssw0rd';
        $serverName = 'http://phrasea.io';
        $dataPath = '/tmp';
        $storagePaths = [
            'subdefs'           => $dataPath,
            'download'          => $dataPath,
            'lazaret'           => $dataPath,
            'caption'           => $dataPath,
            'worker_tmp_files'  => $dataPath
        ];
        $template = 'fr-simple';

        $infoDb = Yaml::parse(file_get_contents(__DIR__ . '/../../../../../../resources/hudson/InstallDBs.yml'));

        $helperSet = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')
            ->disableOriginalConstructor()
            ->getMock();

        $dialog = $this->getMockBuilder('Symfony\Component\Console\Helper\DialogHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $helperSet->expects($this->once())
            ->method('get')
            ->with('dialog')
            ->will($this->returnValue($dialog));

        $input->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function ($option) use ($infoDb, $template, $email, $password, $serverName, $dataPath) {
                switch ($option) {
                    case 'appbox':
                        return $infoDb['database']['ab_name'];
                        break;
                    case 'databox':
                        return $infoDb['database']['db_name'];
                        break;
                    case 'db-template':
                        return $template;
                        break;
                    case 'email':
                        return $email;
                        break;
                    case 'password':
                        return $password;
                        break;
                    case 'download-path':
                    case 'lazaret-path':
                    case 'caption-path':
                    case 'scheduler-locks-path':
                    case 'worker-tmp-files':
                    case 'data-path':
                        return $dataPath;
                        break;
                    case 'server-name':
                        return $serverName;
                        break;
                    case 'yes':
                        return true;
                        break;
                    case 'db-host':
                        return $infoDb['database']['host'];
                        break;
                    case 'db-port':
                        return $infoDb['database']['port'];
                        break;
                    case 'db-user':
                        return $infoDb['database']['user'];
                        break;
                    case 'db-password':
                        return $infoDb['database']['password'];
                        break;
                    case 'es-host':
                        return 'localhost';
                    case 'es-port':
                        return 9200;
                    case 'es-index':
                        return 'phrasea_test';
                    default:
                        return '';
                }
            }));

        self::$DI['cli']['phraseanet.installer'] = $this->getMockBuilder('Alchemy\Phrasea\Setup\Installer')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['cli']['phraseanet.installer']->expects($this->once())
            ->method('install')
            ->with($email, $password, $this->isInstanceOf('Doctrine\DBAL\Driver\Connection'), $serverName, $storagePaths, $this->isInstanceOf('Doctrine\DBAL\Driver\Connection'), $template, $this->anything());

        $structureTemplate = self::$DI['cli']['phraseanet.structure-template'];

        $command = new Install('system:check', $structureTemplate);
        $command->setHelperSet($helperSet);
        $command->setContainer(self::$DI['cli']);
        $this->assertEquals(0, $command->execute($input, $output));
    }
}
