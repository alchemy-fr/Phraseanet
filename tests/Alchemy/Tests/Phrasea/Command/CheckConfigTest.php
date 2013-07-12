<?php

namespace Alchemy\Tests\Phrasea\Command;

use Alchemy\Phrasea\Command\CheckConfig;

class CheckConfigTest extends \PhraseanetPHPUnitAbstract
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new CheckConfig('check:config');
        $command->setContainer(self::$DI['app']);
        $this->assertLessThan(2, $command->execute($input, $output));
    }
}
