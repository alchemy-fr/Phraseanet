<?php

namespace Alchemy\Tests\Phrasea;

use Alchemy\Phrasea\CLI;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class CLITest extends \PhraseanetTestCase
{
    public function testsEmailWithoutQueue()
    {
        $app = new CLI('Phrasea');

        $spool = $this->createMock('Swift_Spool');
        $spool->expects($this->once())
            ->method('flushQueue')
            ->with($this->isInstanceOf('Swift_Transport'));

        $app['swiftmailer.spooltransport'] = $this->createMock('Swift_SpoolTransport');

        $app['swiftmailer.spooltransport']->expects($this->once())
            ->method('getSpool')
            ->will($this->returnValue($spool));

        $app['dispatcher']->dispatch(new \Symfony\Contracts\EventDispatcher\Event(), 'phraseanet.notification.sent');
    }
}
