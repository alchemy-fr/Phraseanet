<?php

namespace Alchemy\Phrasea\Command\Compile;

use Alchemy\Phrasea\Command\Compile\Configuration;

class ConfigurationTest extends \PhraseanetPHPUnitAbstract
{
    public function testExecute()
    {
        $command = new Configuration();
        $command->setContainer(self::$DI['cli']);

        self::$DI['cli']['phraseanet.configuration'] = $this->getMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');
        self::$DI['cli']['phraseanet.configuration']->expects($this->once())
            ->method('compileAndWrite');

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command->execute($input, $output);
    }
}
