<?php

namespace Alchemy\Tests\Phrasea\Command\Developper;

use Alchemy\Phrasea\Command\Developer\ComposerInstall;

class ComposerInstallTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['driver.composer'] = $this->getMockBuilder('Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['cli']['driver.composer']->expects($this->at(0))
            ->method('command')
            ->with('self-update');

        self::$DI['cli']['driver.composer']->expects($this->at(1))
            ->method('command')
            ->with(['install', '--optimize-autoloader', '--quiet', '--no-interaction', '--dev']);

        $command = new ComposerInstall();
        $command->setContainer(self::$DI['cli']);

        $this->assertEquals(0, $command->execute($input, $output));
    }
}
