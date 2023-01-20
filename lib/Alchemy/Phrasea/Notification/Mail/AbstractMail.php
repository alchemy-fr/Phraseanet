<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Notification\EmitterInterface;
use Alchemy\Phrasea\Notification\ReceiverInterface;

abstract class AbstractMail implements MailInterface
{
    const MAIL_SKIN = 'default';

    /** @var string| null */
    protected $locale = null;
    /** @var Application */
    protected $app;
    /** @var EmitterInterface */
    protected $emitter;
    /** @var ReceiverInterface */
    protected $receiver;
    /** @var string */
    protected $message;
    /** @var string */
    protected $logoUrl;
    /** @var string */
    protected $logoText;
    /** @var \DateTime */
    protected $expiration;
    /** @var string */
    protected $url;
    /** @var bool  */
    protected $hasFooterText = true;

    public function __construct(Application $app, ReceiverInterface $receiver, EmitterInterface $emitter = null, $message = null)
    {
        $this->app = $app;
        $this->emitter = $emitter;
        $this->receiver = $receiver;
        $this->message = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function renderHTML()
    {
        return $this->app['twig']->render('email-template.html.twig', [
            'phraseanetURL'     => $this->getPhraseanetURL(),
            'phraseanetTitle'   => $this->getPhraseanetTitle(),
            'logoUrl'           => $this->getLogoUrl(),
            'logoText'          => $this->getLogoText(),
            'subject'           => $this->getSubject(),
            'senderName'        => $this->getEmitter() ? $this->getEmitter()->getName() : null,
            'senderMail'        => $this->getEmitter() ? $this->getEmitter()->getEmail() : null,
            'messageText'       => $this->getMessage(),
            'expiration'        => $this->getExpiration(),
            'buttonUrl'         => $this->getButtonURL(),
            'buttonText'        => $this->getButtonText(),
            'mailSkin'          => $this->getMailSkin(),
            'emailLocale'       => $this->getLocale(),
            'hasFooterText'     => $this->getDisplayFooterText()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPhraseanetTitle()
    {
        return $this->app['conf']->get(['registry', 'general', 'title']);
    }

    /**
     * {@inheritdoc}
     */
    public function getPhraseanetURL()
    {
        return $this->app->url('root');
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getDisplayFooterText()
    {
        return $this->hasFooterText;
    }

    public function setDisplayFooterText(bool $hasFooterText)
    {
        $this->hasFooterText = !!$hasFooterText;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogoUrl()
    {
        return $this->logoUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogoUrl($url)
    {
        $this->logoUrl = $url;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogoText()
    {
        return $this->logoText ?: $this->getPhraseanetTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function setLogoText($text)
    {
        $this->logoText = $text;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmitter()
    {
        return $this->emitter;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmitter(EmitterInterface $emitter = null)
    {
        $this->emitter = $emitter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * {@inheritdoc}
     */
    public function setReceiver(ReceiverInterface $receiver)
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * {@inheritdoc}
     */
    public function setButtonUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getMailSkin()
    {
        return self::MAIL_SKIN;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getSubject();

    /**
     * {@inheritdoc}
     */
    abstract public function getMessage();

    /**
     * {@inheritdoc}
     */
    abstract public function getButtonText();

    /**
     * {@inheritdoc}
     */
    abstract public function getButtonURL();

    /**
     * Creates an Email
     *
     * @param Application       $app
     * @param ReceiverInterface $receiver
     * @param EmitterInterface  $emitter
     * @param string            $message
     *
     * @return static
     */
    public static function create(Application $app, ReceiverInterface $receiver, EmitterInterface $emitter = null, $message = null)
    {
        return new static($app, $receiver, $emitter, $message);
    }
}
