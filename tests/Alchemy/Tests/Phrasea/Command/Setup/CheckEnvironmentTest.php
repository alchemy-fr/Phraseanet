<?php

namespace Alchemy\Tests\Phrasea\Command;

use Alchemy\Phrasea\Command\Setup\CheckEnvironment;

class CheckEnvironmentTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new CheckEnvironment('system:check');
        $command->setContainer(self::$DI['cli']);
        $this->assertLessThan(2, $command->execute($input, $output));
    }
}
