<?php

namespace Alchemy\Tests\Phrasea\Command;

use Alchemy\Phrasea\Command\WebsocketServer;

class WebsocketServerTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $sessionType = self::$DI['cli']['conf']->get(['main', 'session', 'type'], 'file');
        self::$DI['cli']['conf']->set(['main', 'session', 'type'], 'memcached');

        self::$DI['cli']['ws.server'] = $this->getMockBuilder('Ratchet\App')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['cli']['ws.server']->expects($this->once())
            ->method('run');

        $command = new WebsocketServer('websocketserver');
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);

        self::$DI['cli']['conf']->set(['main', 'session', 'type'], $sessionType);
    }
}
