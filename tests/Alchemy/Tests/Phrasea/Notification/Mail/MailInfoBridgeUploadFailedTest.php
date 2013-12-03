<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailInfoBridgeUploadFailed;
use Alchemy\Phrasea\Exception\LogicException;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoBridgeUploadFailed
 */
class MailInfoBridgeUploadFailedTest extends MailWithLinkTestCase
{
    public function testSetAdapter()
    {
        $mail = $this->getMail();

        $this->assertEquals('An upload on %bridge_adapter% failed, the resaon is : %reason%', $mail->getMessage());
    }

    public function testSHouldThrowALogicExceptionIfNoAdapterProvided()
    {
        $mail = MailInfoBridgeUploadFailed::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $mail->setReason('you\'re too fat');

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function testSHouldThrowALogicExceptionIfNoReasonProvided()
    {
        $mail = MailInfoBridgeUploadFailed::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $mail->setAdapter('dailymotion');

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function testSetReason()
    {
        $mail = $this->getMail();

        $this->assertEquals('An upload on %bridge_adapter% failed, the resaon is : %reason%', $mail->getMessage());
    }

    public function getMail()
    {
        $mail = MailInfoBridgeUploadFailed::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $mail->setAdapter('dailymotion');
        $mail->setReason('you\'re too fat');

        return $mail;
    }
}
