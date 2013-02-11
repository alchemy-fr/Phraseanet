<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailInterface;

abstract class MailTestCase extends \PhraseanetPHPUnitAbstract
{
    public function testGetSubject()
    {
        $this->assertInternalType('string', $this->getMail()->getSubject());
    }

    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->getMail()->getMessage());
    }

    public function testGetButtonText()
    {
        if (null === $this->getMail()->getButtonURL() && null === $this->getMail()->getButtonText()) {
            return;
        }

        $this->assertInternalType('string', $this->getMail()->getButtonText());
    }

    public function testGetButtonURL()
    {
        if (null === $this->getMail()->getButtonURL() && null === $this->getMail()->getButtonText()) {
            return;
        }

        $this->assertTrue(0 === stripos($this->getMail()->getButtonURL(), 'http://'), 'Checking that URL button points to an absolute URL');
    }

    public function getApp()
    {
        return self::$DI['app'];
    }

    public function getReceiverMock()
    {
        return $this->getMock('Alchemy\Phrasea\Notification\ReceiverInterface');
    }

    public function getEmitterMock()
    {
        return $this->getMock('Alchemy\Phrasea\Notification\EmitterInterface');
    }

    public function getMessage()
    {
        return 'Lorem ipsum dolor';
    }

    public function getUrl()
    {
        return 'http://www.example.com';
    }

    public function getExpiration()
    {
        return new \DateTime();
    }

    /**
     * @return MailInterface
     */
    abstract public function getMail();

    public function assertContainsString($expected, $message)
    {
        $this->assertTrue(false !== stripos($message, $expected));
    }
}
