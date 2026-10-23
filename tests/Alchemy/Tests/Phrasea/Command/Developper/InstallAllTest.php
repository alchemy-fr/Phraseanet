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

        self::$DI['cli']['console'] = $this->createStub('Symfony\Component\Console\Application');

        $n = 0;
        foreach ([
            'dependencies:composer'
        ] as $name) {
            $command = $this->createMock('Symfony\Component\Console\Command\Command');
            $command->expects($this->once())
                ->method('execute')
                ->with($input, $output)
                ->will($this->returnValue(0));

            self::$DI['cli']['console']->expects($this->at($n))
                ->method('get')
                ->with($name)
                ->will($this->returnValue($command));
            $n++;
        }

        $command = new InstallAll();
        $command->setContainer(self::$DI['cli']);

        $this->assertEquals(0, $command->execute($input, $output));
    }
}
