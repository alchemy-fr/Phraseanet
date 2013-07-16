<?php

namespace Alchemy\Tests\Phrasea\Command\Developper;

use Alchemy\Phrasea\Command\Developer\BowerInstall;

class BowerInstallTest extends \PhraseanetPHPUnitAbstract
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $input->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function ($name) {
                switch ($name) {
                    case 'attempts':
                        return 4;
                    default:
                        return null;
                }
            }));

        $input->expects($this->any())
            ->method('getArgument')
            ->will($this->returnCallback(function ($name) {
                switch ($name) {
                    default:
                        return null;
                }
            }));

        self::$DI['cli']['filesystem'] = $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['cli']['filesystem']->expects($this->once())
            ->method('remove')
            ->with(self::$DI['cli']['root.path'].'/www/assets');

        self::$DI['cli']['driver.bower'] = $this->getMockBuilder('Alchemy\Phrasea\Command\Developer\Utils\BowerDriver')
            ->disableOriginalConstructor()
            ->getMock();

        $processBuilder = $this->getMock('Alchemy\BinaryDriver\ProcessBuilderFactoryInterface');
        $processBuilder->expects($this->once())
            ->method('getBinary')
            ->will($this->returnValue('/opt/local/bin/bower'));

        self::$DI['cli']['driver.bower']->expects($this->at(0))
            ->method('getProcessBuilderFactory')
            ->will($this->returnValue($processBuilder));

        self::$DI['cli']['driver.bower']->expects($this->at(1))
            ->method('command')
            ->with('-v')
            ->will($this->returnValue('1.0.0-alpha.5'));

        self::$DI['cli']['driver.bower']->expects($this->at(2))
            ->method('command')
            ->with(array('cache', 'clean'));

        self::$DI['cli']['driver.bower']->expects($this->at(3))
            ->method('command')
            ->with('install');

        $command = new BowerInstall();
        $command->setContainer(self::$DI['cli']);

        $this->assertEquals(0, $command->execute($input, $output));
    }
}
