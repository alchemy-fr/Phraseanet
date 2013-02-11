<?php

namespace Alchemy\Tests\Phrasea\Notification;

use Alchemy\Phrasea\Notification\Deliverer;
use Alchemy\Phrasea\Exception\LogicException;

class DelivererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliver()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $mail->expects($this->any())
            ->method('getReceiver')
            ->will($this->returnValue($this->getReceiverMock()));

        $mailer = $this->getMailerMock();
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('\Swift_Message'))
            ->will($this->returnValue(42));

        $deliverer = new Deliverer($mailer, $this->getEventDispatcherMock(), $this->getEmitterMock());
        $this->assertEquals(42, $deliverer->deliver($mail));
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliverWithoutReceiverShouldThrowAnException()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $deliverer = new Deliverer($this->getMailerMock(), $this->getEventDispatcherMock(), $this->getEmitterMock());

        try {
            $deliverer->deliver($mail);
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {
            $this->assertEquals('You must provide a receiver for a mail notification', $e->getMessage());
        }
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliverWithoutReceipt()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $mail->expects($this->any())
            ->method('getReceiver')
            ->will($this->returnValue($this->getReceiverMock()));

        $catchEmail = null;

        $mailer = $this->getMailerMock();
        $mailer->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function($email) use (&$catchEmail) {
                $catchEmail = $email;
            }));

        $deliverer = new Deliverer($mailer, $this->getEventDispatcherMock(), $this->getEmitterMock());
        $deliverer->deliver($mail);

        /* @var $catchEmail \Swift_Message */
        $this->assertNull($catchEmail->getReadReceiptTo());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliverWithAReadReceiptWithoutEmitterShouldThrowException()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $mail->expects($this->any())
            ->method('getReceiver')
            ->will($this->returnValue($this->getReceiverMock()));

        $deliverer = new Deliverer($this->getMailerMock(), $this->getEventDispatcherMock(), $this->getEmitterMock());

        try {
            $deliverer->deliver($mail, true);
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {
            $this->assertEquals('You must provide an emitter for a ReadReceipt', $e->getMessage());
        }
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliverWithReadReceipt()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $mail->expects($this->any())
            ->method('getReceiver')
            ->will($this->returnValue($this->getReceiverMock()));

        $name = 'replyto-name';
        $email = 'replyto-email@domain.com';

        $emitter = $this->getMock('Alchemy\Phrasea\Notification\EmitterInterface');
        $emitter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $emitter->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue($email));

        $mail->expects($this->any())
            ->method('getEmitter')
            ->will($this->returnValue($emitter));

        $catchEmail = null;

        $mailer = $this->getMailerMock();
        $mailer->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function($email) use (&$catchEmail) {
                $catchEmail = $email;
            }));

        $deliverer = new Deliverer($mailer, $this->getEventDispatcherMock(), $this->getEmitterMock());
        $deliverer->deliver($mail, true);

        /* @var $catchEmail \Swift_Message */
        $this->assertEquals(array($email => $name), $catchEmail->getReadReceiptTo());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliverWithRightSubject()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $mail->expects($this->any())
            ->method('getReceiver')
            ->will($this->returnValue($this->getReceiverMock()));

        $subject = 'Un joli message';

        $mail->expects($this->any())
            ->method('getSubject')
            ->will($this->returnValue($subject));

        $catchEmail = null;

        $mailer = $this->getMailerMock();
        $mailer->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function($email) use (&$catchEmail) {
                $catchEmail = $email;
            }));

        $deliverer = new Deliverer($mailer, $this->getEventDispatcherMock(), $this->getEmitterMock());
        $deliverer->deliver($mail);

        /* @var $catchEmail \Swift_Message */
        $this->assertEquals(0, strpos($catchEmail->getSubject(), $subject));
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliverWithRightPrefix()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $mail->expects($this->any())
            ->method('getReceiver')
            ->will($this->returnValue($this->getReceiverMock()));

        $subject = 'Un joli message';

        $mail->expects($this->any())
            ->method('getSubject')
            ->will($this->returnValue($subject));

        $catchEmail = null;

        $mailer = $this->getMailerMock();
        $mailer->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function($email) use (&$catchEmail) {
                $catchEmail = $email;
            }));

        $prefix = 'prefix' . mt_rand();

        $deliverer = new Deliverer($mailer, $this->getEventDispatcherMock(), $this->getEmitterMock(), $prefix);
        $deliverer->deliver($mail);

        /* @var $catchEmail \Swift_Message */
        $this->assertEquals(0, strpos($catchEmail->getSubject(), $prefix));
        $this->assertNotEquals(false, strpos($catchEmail->getSubject(), $subject));
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliverWithFromHeader()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $mail->expects($this->any())
            ->method('getReceiver')
            ->will($this->returnValue($this->getReceiverMock()));

        $name = 'emitter-name';
        $email = 'emitter-email@domain.com';

        $emitter = $this->getEmitterMock();
        $emitter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $emitter->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue($email));

        $catchEmail = null;

        $mailer = $this->getMailerMock();
        $mailer->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function($email) use (&$catchEmail) {
                $catchEmail = $email;
            }));

        $deliverer = new Deliverer($mailer, $this->getEventDispatcherMock(), $emitter);
        $deliverer->deliver($mail);

        /* @var $catchEmail \Swift_Message */
        $this->assertEquals(array($email => $name), $catchEmail->getFrom());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliverWithToHeader()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $name = 'receiver-name';
        $email = 'receiver-email@domain.com';

        $receiver = $this->getMock('Alchemy\Phrasea\Notification\ReceiverInterface');
        $receiver->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $receiver->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue($email));

        $mail->expects($this->any())
            ->method('getReceiver')
            ->will($this->returnValue($receiver));

        $catchEmail = null;

        $mailer = $this->getMailerMock();
        $mailer->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function($email) use (&$catchEmail) {
                $catchEmail = $email;
            }));

        $deliverer = new Deliverer($mailer, $this->getEventDispatcherMock(), $this->getEmitterMock());
        $deliverer->deliver($mail);

        /* @var $catchEmail \Swift_Message */
        $this->assertEquals(array($email => $name), $catchEmail->getTo());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Deliverer::deliver
     */
    public function testDeliverWithReplyToHeader()
    {
        $mail = $this->getMock('Alchemy\Phrasea\Notification\Mail\MailInterface');

        $name = 'replyto-name';
        $email = 'replyto-email@domain.com';

        $emitter = $this->getMock('Alchemy\Phrasea\Notification\EmitterInterface');
        $emitter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $emitter->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue($email));

        $mail->expects($this->any())
            ->method('getEmitter')
            ->will($this->returnValue($emitter));

        $mail->expects($this->any())
            ->method('getReceiver')
            ->will($this->returnValue($this->getReceiverMock()));

        $catchEmail = null;

        $mailer = $this->getMailerMock();
        $mailer->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function($email) use (&$catchEmail) {
                $catchEmail = $email;
            }));

        $deliverer = new Deliverer($mailer, $this->getEventDispatcherMock(), $this->getEmitterMock());
        $deliverer->deliver($mail);

        /* @var $catchEmail \Swift_Message */
        $this->assertEquals(array($email => $name), $catchEmail->getReplyTo());
    }

    private function getEventDispatcherMock()
    {
        return $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    private function getMailerMock()
    {
        return $this->getMockBuilder('\Swift_Mailer')->disableOriginalConstructor()->getMock();
    }

    private function getEmitterMock()
    {
        return $this->getMock('Alchemy\Phrasea\Notification\EmitterInterface');
    }

    private function getReceiverMock()
    {
        $receiver = $this->getMock('Alchemy\Phrasea\Notification\ReceiverInterface');

        $receiver->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('name'));

        $receiver->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('name@domain.com'));

        return $receiver;
    }
}
