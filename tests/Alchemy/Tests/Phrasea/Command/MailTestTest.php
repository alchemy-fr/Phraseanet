<?php

namespace Alchemy\Tests\Phrasea\Command;

use Alchemy\Phrasea\Command\MailTest;

class MailTestTest extends \PhraseanetPHPUnitAbstract
{
    public function testMailIsSent()
    {
        $capturedEmail = null;

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())
            ->method('getArgument')
            ->with($this->equalTo('email'))
            ->will($this->returnValue('test-mail@phraseanet.com'));

        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['cli']['notification.deliverer']->expects($this->once())
            ->method('deliver')
            ->with($this->isInstanceOf('Alchemy\Phrasea\Notification\Mail\MailTest'), $this->equalTo(null))
            ->will($this->returnCallback(function ($email) use (&$capturedEmail) {
                $capturedEmail = $email;
            }));

        $command = new MailTest('mail:test');
        $command->setContainer(self::$DI['cli']);
        $result = $command->execute($input, $output);

        $this->assertSame(0, $result);
        $this->assertEquals('test-mail@phraseanet.com', $capturedEmail->getReceiver()->getEmail());
    }
}
