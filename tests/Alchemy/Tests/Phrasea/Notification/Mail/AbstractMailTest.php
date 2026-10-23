<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\AbstractMail;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class AbstractMailTest extends \PhraseanetTestCase
{
    public function testRenderHTML()
    {
        $message = 'HTML sucks !';
        $html = '<html><head></head><body>Hello World!</body></html>';

        $registry = $this->getRegistryMock();

        $urlGenerator = $this->createStub('Symfony\Component\Routing\Generator\UrlGenerator');

        $twig = $this->createMock('\Twig\Environment');

        $twig->expects($this->once())
            ->method('render')
            ->will($this->returnValue($html));

        $app = $this->getApplicationMock();
        $app
            ->method('offsetGet')
            ->will($this->returnCallback(function ($offset) use ($twig, $urlGenerator, $registry) {
                switch ($offset) {
                    case 'twig':
                        return $twig;
                        break;
                    case 'url_generator':
                        return $urlGenerator;
                        break;
                    case 'conf':
                        return $registry;
                        break;
                    default:
                        throw new \InvalidArgumentException(sprintf('Unknown offset %s', $offset));
                }
            }));

        $mail = new AbstractTester($app, $this->getReceiverMock(), null, $message);
        $this->assertEquals($html, $mail->renderHTML());
        $this->assertEquals($message, $mail->getMessage());
    }

    public function testGetPhraseanetTitle()
    {
        $registry = $this->getRegistryMock();

        $registry->expects($this->once())
            ->method('get')
            ->with(['registry', 'general', 'title'])
            ->will($this->returnValue('Super Mario'));

        $app = $this->getApplicationMock();
        $app->expects($this->once())
            ->method('offsetGet')
            ->with($this->equalTo('conf'))
            ->will($this->returnValue($registry));

        $mail = new AbstractTester($app, $this->getReceiverMock());
        $this->assertEquals('Super Mario', $mail->getPhraseanetTitle());
    }

    public function testGetPhraseanetURL()
    {
        $app = $this->getApplicationMock();
        $app->expects($this->once())
            ->method('url')
            ->with('root')
            ->will($this->returnValue('http://www.phraseanet.com/BIDULE'));

        $mail = new AbstractTester($app, $this->getReceiverMock());
        $this->assertEquals('http://www.phraseanet.com/BIDULE', $mail->getPhraseanetURL());
    }

    public function testGetLogoUrl()
    {
        $mail = new AbstractTester($this->getApplicationMock(), $this->getReceiverMock());
        $this->assertNull($mail->getLogoUrl());

        $logo = 'http://phraseanet.com/logo.png';
        $mail->setLogoUrl($logo);
        $this->assertEquals($logo, $mail->getLogoUrl());
    }

    public function testGetLogoText()
    {
        $app = $this->getApplicationMock();

        $app
            ->method('offsetGet')
            ->with($this->equalTo('conf'))
            ->will($this->returnValue($this->getRegistryMock()));

        $mail = new AbstractTester($app, $this->getReceiverMock());
        $this->assertNull($mail->getLogoText());

        $text = 'hello logo';
        $mail->setLogoText($text);
        $this->assertEquals($text, $mail->getLogoText());
    }

    public function testGetEmitter()
    {
        $mail = new AbstractTester($this->getApplicationMock(), $this->getReceiverMock());
        $this->assertNull($mail->getEmitter());

        $emitter = $this->getEmitterMock();

        $mail->setEmitter($emitter);
        $this->assertEquals($emitter, $mail->getEmitter());

        $mail->setEmitter(null);
        $this->assertNull($mail->getEmitter());
    }

    public function testGetEmitterPassedOnConstructor()
    {
        $emitter = $this->getEmitterMock();

        $mail = new AbstractTester($this->getApplicationMock(), $this->getReceiverMock(), $emitter);
        $this->assertEquals($emitter, $mail->getEmitter());
    }

    public function testGetReceiver()
    {
        $receiver = $this->getReceiverMock();

        $mail = new AbstractTester($this->getApplicationMock(), $receiver);
        $this->assertEquals($receiver, $mail->getReceiver());

        $receiver2 = $this->getReceiverMock();
        $mail->setReceiver($receiver2);
        $this->assertEquals($receiver2, $mail->getReceiver());
    }

    public function testGetExpiration()
    {
        $mail = new AbstractTester($this->getApplicationMock(), $this->getReceiverMock());
        $this->assertNull($mail->getExpiration());
    }

    public function testCreate()
    {
        $app = $this->getApplicationMock();
        $receiver = $this->getReceiverMock();
        $emitter = $this->getEmitterMock();
        $message = 'Un joli message';

        $mail = AbstractTester::create($app, $receiver, $emitter, $message);
        $this->assertEquals($message, $mail->getMessage());
        $this->assertEquals($emitter, $mail->getEmitter());
        $this->assertEquals($receiver, $mail->getReceiver());
    }

    public function testCreateWithoutEmitter()
    {
        $app = $this->getApplicationMock();
        $receiver = $this->getReceiverMock();
        $message = 'Un joli message';

        $mail = AbstractTester::create($app, $receiver, null, $message);
        $this->assertEquals($message, $mail->getMessage());
        $this->assertNull($mail->getEmitter());
        $this->assertEquals($receiver, $mail->getReceiver());
    }

    public function testCreateWithoutMessage()
    {
        $app = $this->getApplicationMock();
        $receiver = $this->getReceiverMock();

        $mail = AbstractTester::create($app, $receiver);
        $this->assertNull($mail->getMessage());
        $this->assertNull($mail->getEmitter());
        $this->assertEquals($receiver, $mail->getReceiver());
    }

    public function testSetButtonUrl()
    {
        $app = $this->getApplicationMock();
        $receiver = $this->getReceiverMock();

        $url = 'http://www.phraseanet.com/#' . mt_rand();

        $mail = AbstractTester::create($app, $receiver);
        $mail->setButtonURL($url);
        $this->assertEquals($url, $mail->getButtonURL());
    }

    private function getMailTest()
    {
        return new AbstractTester($app, $receiver, $emitter, $message);
    }

    protected function getRegistryMock()
    {
        return $this->createMock('Alchemy\Phrasea\Core\Configuration\PropertyAccess');
    }

    protected function getApplicationMock()
    {
        return $this->createMock('Alchemy\Phrasea\Application');
    }

    protected function getReceiverMock()
    {
        return $this->createMock('Alchemy\Phrasea\Notification\ReceiverInterface');
    }

    protected function getEmitterMock()
    {
        return $this->createMock('Alchemy\Phrasea\Notification\EmitterInterface');
    }
}

class AbstractTester extends AbstractMail
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
