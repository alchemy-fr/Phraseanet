<?php

namespace Alchemy\Tests\Phrasea\Command\Developper;

use Alchemy\Phrasea\Command\Developer\BowerInstall;

class BowerInstallTest extends \PhraseanetPHPUnitAbstract
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['filesystem'] = $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['cli']['driver.bower'] = $this->getMockBuilder('Alchemy\Phrasea\Command\Developer\Utils\BowerDriver')
            ->disableOriginalConstructor()

            ->getMock();
        self::$DI['cli']['driver.grunt'] = $this->getMockBuilder('Alchemy\Phrasea\Command\Developer\Utils\GruntDriver')
            ->disableOriginalConstructor()
            ->getMock();

        $processBuilder = $this->getMock('Alchemy\BinaryDriver\ProcessBuilderFactoryInterface');
        $processBuilder->expects($this->any())
            ->method('getBinary');

        self::$DI['cli']['driver.bower']->expects($this->at(0))
            ->method('getProcessBuilderFactory')
            ->will($this->returnValue($processBuilder));

        self::$DI['cli']['driver.bower']->expects($this->at(1))
            ->method('command')
            ->with('-v')
            ->will($this->returnValue('1.0.0-alpha.5'));

        self::$DI['cli']['driver.grunt']->expects($this->at(0))
            ->method('getProcessBuilderFactory')
            ->will($this->returnValue($processBuilder));

        self::$DI['cli']['driver.grunt']->expects($this->at(1))
            ->method('command')
            ->with('--version')
            ->will($this->returnValue('4.0.1'));

        self::$DI['cli']['driver.grunt']->expects($this->at(2))
            ->method('command')
            ->with('build-assets');

        $command = new BowerInstall();
        $command->setContainer(self::$DI['cli']);

        $this->assertEquals(0, $command->execute($input, $output));
    }
}
