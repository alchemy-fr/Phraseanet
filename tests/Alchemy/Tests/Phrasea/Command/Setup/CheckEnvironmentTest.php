<?php

namespace Alchemy\Tests\Phrasea\Command;

use Alchemy\Phrasea\Command\Setup\CheckEnvironment;

class CheckEnvironmentTest extends \PhraseanetPHPUnitAbstract
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new CheckEnvironment('system:check');
        $this->assertEquals(0, $command->execute($input, $output));
    }
}
