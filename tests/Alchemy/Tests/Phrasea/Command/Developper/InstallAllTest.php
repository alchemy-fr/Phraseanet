<?php

namespace Alchemy\Tests\Phrasea\Command\Developper;

use Alchemy\Phrasea\Command\Developer\InstallAll;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class InstallAllTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['console'] = $this->createMock('Symfony\Component\Console\Application');

        $command = $this->createMock('Symfony\Component\Console\Command\Command');
        $command->expects($this->once())
            ->method('run')
            ->with($input, $output)
            ->willReturn(0);

        self::$DI['cli']['console']->expects($this->once())
            ->method('get')
            ->with('dependencies:composer')
            ->willReturn($command);

        $command = new InstallAll();
        $command->setContainer(self::$DI['cli']);

        $this->assertEquals(0, $command->execute($input, $output));
    }
}
