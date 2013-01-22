<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Receiver;

abstract class AbstractMail implements MailInterface
{
    protected $app;
    /** @var Emitter */
    protected $emitter;
    /** @var Receiver */
    protected $receiver;
    protected $message;

    public function __construct(Application $app, Receiver $receiver, Emitter $emitter = null, $message = null)
    {
        $this->app = $app;
        $this->emitter = $emitter;
        $this->receiver = $receiver;
        $this->message = $message;
    }

    public function renderHTML()
    {
        return $this->app['twig']->render('email-template.html.twig', array(
                'phraseanetURL' => $this->phraseanetURL(),
                'logoUrl'       => $this->logoUrl(),
                'logoText'      => $this->logoText(),
                'subject'       => $this->subject(),
                'senderName'    => $this->emitter() ? $this->emitter()->getName() : null,
                'senderMail'    => $this->emitter() ? $this->emitter()->getEmail() : null,
                'messageText'   => $this->message(),
                'expirationMessage'   => $this->getExpirationMessage(),
                'buttonUrl'     => $this->buttonURL(),
                'buttonText'    => $this->buttonText(),
            ));
    }

    public function phraseanetURL()
    {
        return $this->app['phraseanet.registry']->get('GV_ServerName');
    }

    public function logoUrl()
    {
        return;
    }

    public function logoText()
    {
        return $this->app['phraseanet.registry']->get('GV_homeTitle');
    }

    public function emitter()
    {
        return $this->emitter;
    }

    public function receiver()
    {
        return $this->receiver;
    }

    public function getExpirationMessage()
    {
        return null;
    }

    abstract public function subject();

    abstract public function message();

    abstract public function buttonText();

    abstract public function buttonURL();

    public static function create(Application $app, Receiver $receiver, Emitter $emitter = null, $message = null)
    {
        return new static($app['twig'], $receiver, $emitter, $message);
    }
}
