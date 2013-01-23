<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\AbstractMail;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;

class AbstractMailTest extends \PHPUnit_Framework_TestCase
{

    private function getMailTest()
    {
        return new AbstractTester($app, $receiver, $emitter, $message);
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::renderHTML
     */
    public function testRenderHTML()
    {
        $message = 'HTML sucks !';
        $html = '<html><head></head><body>Hello World!</body></html>';

        $registry = $this->getRegistryMock();

        $urlGenerator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $twig = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $twig->expects($this->once())
            ->method('render')
            ->will($this->returnValue($html));

        $app = $this->getApplicationMock();
        $app->expects($this->any())
            ->method('offsetGet')
            ->will($this->returnCallback(function ($offset) use ($twig, $urlGenerator, $registry) {
                        switch ($offset) {
                            case 'twig':
                                return $twig;
                                break;
                            case 'url_generator':
                                return $urlGenerator;
                                break;
                            case 'phraseanet.registry':
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

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::getPhraseanetTitle
     */
    public function testGetPhraseanetTitle()
    {
        $registry = $this->getRegistryMock();

        $registry->expects($this->once())
            ->method('get')
            ->with('GV_homeTitle')
            ->will($this->returnValue('Super Mario'));

        $app = $this->getApplicationMock();
        $app->expects($this->once())
            ->method('offsetGet')
            ->with($this->equalTo('phraseanet.registry'))
            ->will($this->returnValue($registry));

        $mail = new AbstractTester($app, $this->getReceiverMock());
        $this->assertEquals('Super Mario', $mail->getPhraseanetTitle());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::getPhraseanetURL
     */
    public function testGetPhraseanetURL()
    {
        $routes = new RouteCollection();
        $routes->add('root', new Route('/BIDULE'));

        $urlGenerator = new UrlGenerator($routes, new RequestContext('', 'GET', 'www.phraseanet.com'));

        $app = $this->getApplicationMock();
        $app->expects($this->once())
            ->method('offsetGet')
            ->with($this->equalTo('url_generator'))
            ->will($this->returnValue($urlGenerator));

        $mail = new AbstractTester($app, $this->getReceiverMock());
        $this->assertEquals('http://www.phraseanet.com/BIDULE', $mail->getPhraseanetURL());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::getLogoUrl
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::setLogoUrl
     */
    public function testGetLogoUrl()
    {
        $mail = new AbstractTester($this->getApplicationMock(), $this->getReceiverMock());
        $this->assertNull($mail->getLogoUrl());

        $logo = 'http://phraseanet.com/logo.png';
        $mail->setLogoUrl($logo);
        $this->assertEquals($logo, $mail->getLogoUrl());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::getLogoText
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::setLogoText
     */
    public function testGetLogoText()
    {
        $app = $this->getApplicationMock();

        $app->expects($this->any())
            ->method('offsetGet')
            ->with($this->equalTo('phraseanet.registry'))
            ->will($this->returnValue($this->getRegistryMock()));

        $mail = new AbstractTester($app, $this->getReceiverMock());
        $this->assertNull($mail->getLogoText());

        $text = 'hello logo';
        $mail->setLogoText($text);
        $this->assertEquals($text, $mail->getLogoText());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::getEmitter
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::setEmitter
     */
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

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::getEmitter
     */
    public function testGetEmitterPassedOnConstructor()
    {
        $emitter = $this->getEmitterMock();

        $mail = new AbstractTester($this->getApplicationMock(), $this->getReceiverMock(), $emitter);
        $this->assertEquals($emitter, $mail->getEmitter());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::getReceiver
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::setReceiver
     */
    public function testGetReceiver()
    {
        $receiver = $this->getReceiverMock();

        $mail = new AbstractTester($this->getApplicationMock(), $receiver);
        $this->assertEquals($receiver, $mail->getReceiver());

        $receiver2 = $this->getReceiverMock();
        $mail->setReceiver($receiver2);
        $this->assertEquals($receiver2, $mail->getReceiver());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::getExpirationMessage
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::setExpirationMessage
     */
    public function testGetExpirationMessage()
    {
        $mail = new AbstractTester($this->getApplicationMock(), $this->getReceiverMock());
        $this->assertNull($mail->getExpirationMessage());

        $expiration = $this->getMock('\DateTime');

        $mail->setExpirationMessage($expiration);
        $this->assertEquals($expiration, $mail->getExpirationMessage());

        $mail->setExpirationMessage(null);
        $this->assertNull($mail->getExpirationMessage());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::create
     */
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

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::create
     */
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

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\AbstractMail::create
     */
    public function testCreateWithoutMessage()
    {
        $app = $this->getApplicationMock();
        $receiver = $this->getReceiverMock();

        $mail = AbstractTester::create($app, $receiver);
        $this->assertNull($mail->getMessage());
        $this->assertNull($mail->getEmitter());
        $this->assertEquals($receiver, $mail->getReceiver());
    }

    private function getRegistryMock()
    {
        return $this->getMockBuilder('registry')
                ->disableOriginalConstructor()
                ->getMock();
    }

    private function getApplicationMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Application')
                ->disableOriginalConstructor()
                ->getMock();
    }

    private function getReceiverMock()
    {
        return $this->getMock('Alchemy\Phrasea\Notification\ReceiverInterface');
    }

    private function getEmitterMock()
    {
        return $this->getMock('Alchemy\Phrasea\Notification\EmitterInterface');
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
