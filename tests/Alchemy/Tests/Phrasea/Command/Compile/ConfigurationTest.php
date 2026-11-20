<?php

namespace Alchemy\Tests\Phrasea\Command\Compile;

use Alchemy\Phrasea\Command\Compile\Configuration;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ConfigurationTest extends \PhraseanetTestCase
{
    public function testExecute()
    {
        $command = new Configuration();
        $command->setContainer(self::$DI['cli']);

        self::$DI['cli']['configuration.store'] = $this->createMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');
        self::$DI['cli']['configuration.store']->expects($this->once())
            ->method('compileAndWrite');

        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        $command->execute($input, $output);
    }
}
