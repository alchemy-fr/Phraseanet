<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Plugin\ListPlugin;

class ListPluginTest extends PluginCommandTestCase
{
    public function testExecute()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $table = $this->getMockBuilder('Symfony\Component\Console\Helper\TableHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $table->expects($this->once())
            ->method('setHeaders')
            ->will($this->returnSelf());

        $helperSet = $this->getMockBuilder('Symfony\Component\Console\Helper\HelperSet')
            ->disableOriginalConstructor()
            ->getMock();
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
