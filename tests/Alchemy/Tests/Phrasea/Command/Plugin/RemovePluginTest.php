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
        $input->expects($this->any())
              ->method('getOption')
                ->will($this->returnCallback(function ($option) {
                    if ($option === 'keep-config') {
                        return false;
                    }
                }));

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
            ->with(self::$DI['cli']['plugins.directory'].'/'.$name);

        $result = $command->execute($input, $output);

        $this->assertSame(0, $result);

        $conf = self::$DI['cli']['phraseanet.configuration']->getConfig();
        $this->assertArrayNotHasKey('test-plugin', $conf['plugins']);
    }

    public function testExecuteWithoutRemoveConfig()
    {
        $name = 'test-plugin';

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())
              ->method('getArgument')
              ->with($this->equalTo('name'))
              ->will($this->returnValue($name));
        $input->expects($this->any())
              ->method('getOption')
              ->will($this->returnCallback(function ($option) {
                    if ($option === 'keep-config') {
                        return true;
                    }
                }));

        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new RemovePlugin();
        $command->setContainer(self::$DI['cli']);

        $data = $this->addPluginData();

        self::$DI['cli']['filesystem'] = $this->createFilesystemMock();
        self::$DI['cli']['filesystem']->expects($this->at(0))
            ->method('remove')
            ->with(self::$DI['cli']['root.path'].'/www/plugins/'.$name);

        self::$DI['cli']['filesystem']->expects($this->at(1))
            ->method('remove')
            ->with(self::$DI['cli']['plugins.directory'].'/'.$name);

        $result = $command->execute($input, $output);

        $this->assertSame(0, $result);

        $conf = self::$DI['cli']['phraseanet.configuration']->getConfig();
        $this->assertSame($data, $conf['plugins']['test-plugin']);
    }

    private function addPluginData()
    {
        $data = array('key' => 'value');

        $conf = self::$DI['cli']['phraseanet.configuration']->getConfig();
        $conf['plugins']['test-plugin'] = $data;
        self::$DI['cli']['phraseanet.configuration']->setConfig($conf);

        return $data;
    }
}
