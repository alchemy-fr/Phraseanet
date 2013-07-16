<?php

namespace Alchemy\Tests\Phrasea\Command;

use Alchemy\Phrasea\Command\Setup\Install;

class InstallTest extends \PhraseanetPHPUnitAbstract
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $email = 'romain@neutron.io';
        $password = 'sup4ssw0rd';
        $serverName = 'http://phrasea.io';
        $dataPath = '/tmp';
        $template = 'fr';

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
            ->will($this->returnCallback(function ($option) use ($template, $email, $password, $serverName, $dataPath) {
                switch ($option) {
                    case 'appbox':
                        return 'ab_unitTests';
                        break;
                    case 'databox':
                        return 'db_unitTests';
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
                        return '127.0.0.1';
                        break;
                    case 'db-port':
                        return 3306;
                        break;
                    case 'db-user':
                        return 'root';
                        break;
                    case 'db-password':
                        return '';
                        break;
                    case 'yes':
                        return true;
                        break;
                    default:
                        return;
                }
            }));

        self::$DI['cli']['phraseanet.installer'] = $this->getMockBuilder('Alchemy\Phrasea\Setup\Installer')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['cli']['phraseanet.installer']->expects($this->once())
            ->method('install')
            ->with($email, $password, $this->isInstanceOf('\connection_interface'), $serverName, $dataPath, $this->isInstanceOf('\connection_interface'), $template, $this->anything());

        $command = new Install('system:check');
        $command->setHelperSet($helperSet);
        $command->setContainer(self::$DI['cli']);
        $this->assertEquals(0, $command->execute($input, $output));
    }
}
