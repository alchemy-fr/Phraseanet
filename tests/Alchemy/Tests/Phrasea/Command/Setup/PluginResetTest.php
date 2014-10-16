<?php

namespace Alchemy\Tests\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Setup\PluginsReset;

class PluginResetTest extends \PhraseanetTestCase
{
    public function testRun()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new PluginsReset();
        $command->setContainer(self::$DI['cli']);

        $capturedSource = null;

        self::$DI['cli']['filesystem'] = $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['cli']['filesystem']->expects($this->once())
            ->method('remove')
            ->with(self::$DI['cli']['plugin.path']);
        self::$DI['cli']['filesystem']->expects($this->once())
            ->method('mirror')
            ->with($this->isType('string'), self::$DI['cli']['plugin.path'])
            ->will($this->returnCallback(function ($source, $target) use (&$capturedSource) {
                $capturedSource = $source;
            }));

        $this->assertEquals(0, $command->execute($input, $output));
        $this->assertNotNull($capturedSource);
        $this->assertEquals(realpath(__DIR__ . '/../../../../../../lib/conf.d/plugins'), realpath($capturedSource));
    }
}
