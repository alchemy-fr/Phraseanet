<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Plugin\RemovePlugin;

class RemovePluginTest extends PluginCommandTestCase
{
    public function testExecute()
    {
        $name = 'test-plugin';

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())
            ->method('getArgument')
            ->with($this->equalTo('name'))
            ->will($this->returnValue($name));

        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new RemovePlugin();
        $command->setContainer(self::$DI['app']);

        self::$DI['app']['filesystem'] = $this->createFilesystemMock();
        self::$DI['app']['filesystem']->expects($this->at(0))
            ->method('remove')
            ->with(self::$DI['app']['root.path'].'/www/plugins/'.$name);

        self::$DI['app']['filesystem']->expects($this->at(1))
            ->method('remove')
            ->with(self::$DI['app']['plugins.directory'].'/'.$name);

        $result = $command->execute($input, $output);

        $this->assertSame(0, $result);
    }
}
