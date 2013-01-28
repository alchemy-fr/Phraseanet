<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\AbstractMailWithLink;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\AbstractMailWithLink
 */
class AbstractMailWithLinkTest extends AbstractMailTest
{
    public function testSetExpiration()
    {
        $mail = new AbstractTesterWithLink($this->getApplicationMock(), $this->getReceiverMock());
        $this->assertNull($mail->getExpiration());

        $expiration = $this->getMock('\DateTime');

        $mail->setExpiration($expiration);
        $this->assertEquals($expiration, $mail->getExpiration());

        $mail->setExpiration(null);
        $this->assertNull($mail->getExpiration());
    }

    public function testCreate()
    {
        $message = 'Hello world !';
        $url = 'http://www.phraesanet.com/';
        $expiration = new \DateTime('-3 hours');

        $mail = AbstractTesterWithLink::create($this->getApplicationMock(), $this->getReceiverMock(), $this->getEmitterMock(), $message, $url, $expiration);
        $this->assertEquals($expiration, $mail->getExpiration());
        $this->assertEquals($url, $mail->getButtonURL());
        $this->assertEquals($message, $mail->getMessage());
    }
}

class AbstractTesterWithLink extends AbstractMailWithLink
{
    public $subject;
    public $message;
    public $buttonText;
    public $buttonURL;

    public function getSubject()
    {
        return $this->subject;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getButtonText()
    {
        return $this->buttonText;
    }

    public function getButtonURL()
    {
        return $this->buttonURL;
    }

    public function setButtonURL($url)
    {
        $this->buttonURL = $url;

        return $this;
    }
}
