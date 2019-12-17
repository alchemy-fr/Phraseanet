<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Plugin\EnablePlugin;

/**
 * @group functional
 * @group legacy
 */
class EnablePluginTest extends PluginCommandTestCase
{
    /**
     * @dataProvider provideVariousInitialConfs
     */
    public function testExecute($initial)
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $input->expects($this->once())
              ->method('getArgument')
              ->with($this->equalTo('name'))
              ->will($this->returnValue('test-plugin'));

        $bkp = self::$DI['cli']['conf']->get('plugins');

        self::$DI['cli']['conf']->set(['plugins', 'test-plugin', 'enabled'], $initial);

        $command = new EnablePlugin();
        $command->setContainer(self::$DI['cli']);

        $this->assertSame(0, $command->execute($input, $output));
        $this->assertTrue(self::$DI['cli']['conf']->get(['plugins', 'test-plugin', 'enabled']));

        if(is_null($bkp)) {
            self::$DI['cli']['conf']->remove('plugins');
        }
        else {
            self::$DI['cli']['conf']->set('plugins', $bkp);
        }
    }

    public function provideVariousInitialConfs()
    {
        return [[true], [false]];
    }
}
