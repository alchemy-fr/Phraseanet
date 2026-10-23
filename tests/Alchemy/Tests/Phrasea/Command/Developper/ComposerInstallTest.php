<?php

namespace Alchemy\Tests\Phrasea\Command\Developper;

use Alchemy\Phrasea\Command\Developer\ComposerInstall;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ComposerInstallTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['driver.composer'] = $this->createStub('Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver');

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
