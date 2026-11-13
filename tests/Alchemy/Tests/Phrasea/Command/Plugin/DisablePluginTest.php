<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Plugin\DisablePlugin;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class DisablePluginTest extends PluginCommandTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideVariousInitialConfs')]
    public function testExecute($initial)
    {
        $input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');
        $input->expects($this->once())
              ->method('getArgument')
              ->with($this->equalTo('name'))
              ->will($this->returnValue('test-plugin'));

        $bkp = self::$DI['cli']['conf']->get('plugins');

        self::$DI['cli']['conf']->set(['plugins', 'test-plugin', 'enabled'], $initial);

        $command = new DisablePlugin();
        $command->setContainer(self::$DI['cli']);

        $this->assertSame(0, $command->execute($input, $output));
        $this->assertFalse(self::$DI['cli']['conf']->get(['plugins', 'test-plugin', 'enabled']));

        if(is_null($bkp)) {
            self::$DI['cli']['conf']->remove('plugins');
        }
        else {
            self::$DI['cli']['conf']->set('plugins', $bkp);
        }
    }

    public static function provideVariousInitialConfs()
    {
        return [[true], [false]];
    }
}
