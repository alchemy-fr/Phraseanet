<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Plugin\RemovePlugin;

class RemovePluginTest extends PluginCommandTestCase
{
    public function testExecute()
    {
        $name = 'TestPlugin';

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())
            ->method('getArgument')
            ->with($this->equalTo('name'))
            ->will($this->returnValue($name));

        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new RemovePlugin();
        $command->setContainer(self::$DI['app']);

        self::$DI['app']['filesystem'] = $this->createFilesystemMock();
        self::$DI['app']['filesystem']->expects($this->once())
            ->method('remove')
            ->with(self::$DI['app']['plugins.directory'].'/'.$name);

        $result = $command->execute($input, $output);

        $this->assertSame(0, $result);
    }
}
