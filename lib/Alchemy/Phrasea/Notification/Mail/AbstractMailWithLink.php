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
use Alchemy\Phrasea\Notification\ReceiverInterface;
use Alchemy\Phrasea\Notification\EmitterInterface;

abstract class AbstractMailWithLink extends AbstractMail implements MailWithLinkInterface
{
    protected $expiration;

    /**
     * {@inheritdoc}
     */
    public function setExpiration(\DateTime $expiration = null)
    {
        $this->expiration = $expiration;
    }

    /**
     * Creates a new Mail
     *
     * @param Application       $app
     * @param ReceiverInterface $receiver
     * @param EmitterInterface  $emitter
     * @param string            $message
     * @param string            $url
     * @param \DateTime         $expiration
     *
     * @return static
     */
    public static function create(Application $app, ReceiverInterface $receiver, EmitterInterface $emitter = null, $message = null, $url = null, \DateTime $expiration = null)
    {
        $mail = new static($app, $receiver, $emitter, $message);
        $mail->setButtonUrl($url);
        $mail->setExpiration($expiration);

        return $mail;
    }
}
