<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Plugin\RemovePlugin;

/**
 * @group functional
 * @group legacy
 */
class RemovePluginTest extends PluginCommandTestCase
{
    public function testExecute()
    {
        $name = 'test-plugin';
        @mkdir(self::$DI['cli']['plugin.path']);

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())
              ->method('getArgument')
              ->with($this->equalTo('name'))
              ->will($this->returnValue($name));

        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new RemovePlugin();
        $command->setContainer(self::$DI['cli']);

        self::$DI['cli']['plugins.manager'] = $this->getMockBuilder('Alchemy\Phrasea\Plugin\PluginManager')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['cli']['plugins.manager']->expects($this->once())
            ->method('hasPlugin')
            ->with('test-plugin')
            ->will($this->returnValue(true));

        self::$DI['cli']['filesystem'] = $this->createFilesystemMock();
        self::$DI['cli']['filesystem']->expects($this->at(0))
            ->method('remove')
            ->with(self::$DI['cli']['root.path'].'/www/plugins/'.$name);

        self::$DI['cli']['filesystem']->expects($this->at(1))
            ->method('remove')
            ->with(self::$DI['cli']['plugin.path'].'/'.$name);

        $result = $command->execute($input, $output);

        $this->assertSame(0, $result);

        $conf = self::$DI['cli']['phraseanet.configuration']->getConfig();
        $this->assertArrayNotHasKey('test-plugin', $conf['plugins']);
    }

    private function addPluginData()
    {
        $data = ['key' => 'value'];

        $conf = self::$DI['cli']['phraseanet.configuration']->getConfig();
        $conf['plugins']['test-plugin'] = $data;
        self::$DI['cli']['phraseanet.configuration']->setConfig($conf);

        return $data;
    }
}
