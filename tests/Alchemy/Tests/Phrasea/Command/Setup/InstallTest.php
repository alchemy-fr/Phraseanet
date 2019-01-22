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
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $email = 'jean@dupont.io';
        $password = 'sup4ssw0rd';
        $serverName = 'http://phrasea.io';
        $dataPath = '/tmp';
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
                    case 'databox':
                        return $infoDb['database']['db_name'];
                    case 'db-template':
                        return $template;
                    case 'email':
                        return $email;
                    case 'password':
                        return $password;
                    case 'data-path':
                        return $dataPath;
                    case 'server-name':
                        return $serverName;
                    case 'yes':
                        return true;
                    case 'db-host':
                        return $infoDb['database']['host'];
                    case 'db-port':
                        return $infoDb['database']['port'];
                    case 'db-user':
                        return $infoDb['database']['user'];
                    case 'db-password':
                        return $infoDb['database']['password'];
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
            ->with($email, $password, $this->isInstanceOf('Doctrine\DBAL\Driver\Connection'), $serverName, $dataPath, $this->isInstanceOf('Doctrine\DBAL\Driver\Connection'), $template, $this->anything());

        $structureTemplate = self::$DI['cli']['phraseanet.structure-template'];

        $command = new Install('system:install', $structureTemplate);
        $command->setHelperSet($helperSet);
        $command->setContainer(self::$DI['cli']);
        $this->assertEquals(0, $command->execute($input, $output));
    }
}
