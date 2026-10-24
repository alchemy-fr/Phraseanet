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

        self::$DI['cli']['driver.composer'] = $this->createMock('Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver');

        $commands = [];
        self::$DI['cli']['driver.composer']->expects($this->exactly(2))
            ->method('command')
            ->willReturnCallback(function ($command) use (&$commands) {
                $commands[] = $command;
            });

        $command = new ComposerInstall();
        $command->setContainer(self::$DI['cli']);

        $this->assertEquals(0, $command->execute($input, $output));
        $this->assertSame(['self-update', ['install', '--optimize-autoloader', '--quiet', '--no-interaction', '--dev']], $commands);
    }
}
