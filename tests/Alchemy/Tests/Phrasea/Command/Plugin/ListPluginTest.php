<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Plugin\ListPlugin;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ListPluginTest extends PluginCommandTestCase
{
    public function testExecute()
    {
        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        $table = $this->createMock('Symfony\Component\Console\Helper\TableHelper');
        $table->expects($this->once())
            ->method('setHeaders')
            ->will($this->returnSelf());

        $helperSet = $this->createMock('Symfony\Component\Console\Helper\HelperSet');
        $helperSet->expects($this->once())
            ->method('get')
            ->will($this->returnValue($table));

        $command = new ListPlugin();
        $command->setContainer(self::$DI['cli']);
        $command->setHelperSet($helperSet);

        $result = $command->execute($input, $output);

        $this->assertSame(0, $result);
    }
}
