<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Plugin\ListPlugin;
use Symfony\Component\Console\Output\BufferedOutput;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ListPluginTest extends PluginCommandTestCase
{
    public function testExecute()
    {
        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = new BufferedOutput();

        $command = new ListPlugin();
        $command->setContainer(self::$DI['cli']);

        $result = $command->execute($input, $output);

        $this->assertSame(0, $result);
        $this->assertStringContainsString('Description', $output->fetch());
    }
}
