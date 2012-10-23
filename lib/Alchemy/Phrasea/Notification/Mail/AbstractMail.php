<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Receiver;

abstract class AbstractMail implements MailInterface
{
    private $twig;
    protected $registry;
    private $emitter;
    private $receiver;
    private $message;

    public function __construct(\Twig_Environment $twig, \registry $registry, Receiver $receiver, Emitter $emitter = null, $message = null)
    {
        $this->twig = $twig;
        $this->registry = $registry;
        $this->emitter = $emitter;
        $this->receiver = $receiver;
        $this->message = $message;
    }

    public function renderHTML()
    {
        return $this->twig->render('email-template.html.twig', array(
                'phraseanetURL' => $this->phraseanetURL(),
                'logoUrl'       => $this->logoUrl(),
                'logoText'      => $this->logoText(),
                'subject'       => $this->subject(),
                'senderName'    => $this->emitter() ? $this->emitter()->getName() : null,
                'senderMail'    => $this->emitter() ? $this->emitter()->getEmail() : null,
                'messageText'   => $this->message(),
                'buttonUrl'     => $this->buttonURL(),
                'buttonText'    => $this->buttonText(),
            ));
    }

    public function phraseanetURL()
    {
        return $this->registry->get('GV_ServerName');
    }

    public function logoUrl()
    {
        return;
    }

    public function logoText()
    {
        return $this->registry->get('GV_homeTitle');
    }

    public function emitter()
    {
        return $this->emitter;
    }

    public function receiver()
    {
        return $this->receiver;
    }

    abstract public function subject();

    abstract public function message();

    abstract public function buttonText();

    abstract public function buttonURL();

    public static function create(Application $app, Receiver $receiver, Emitter $emitter = null, $message = null)
    {
        return new static($app['twig'], $app['phraseanet.registry'], $receiver, $emitter, $message);
    }
}
