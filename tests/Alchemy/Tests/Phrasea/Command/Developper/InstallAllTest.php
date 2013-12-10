<?php

namespace Alchemy\Tests\Phrasea\Command\Developper;

use Alchemy\Phrasea\Command\Developer\InstallAll;

class InstallAllTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['console'] = $this->getMockBuilder('Symfony\Component\Console\Application')
            ->disableOriginalConstructor()
            ->getMock();

        $n = 0;
        foreach ([
            'dependencies:composer',
            'dependencies:bower'
        ] as $name) {
            $command = $this->getMockBuilder('Symfony\Component\Console\Command\Command')
                ->setMethods(['execute'])
                ->disableOriginalConstructor()
                ->getMock();
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
