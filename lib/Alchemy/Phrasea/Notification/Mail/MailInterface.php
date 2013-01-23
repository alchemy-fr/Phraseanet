<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\EmitterInterface;
use Alchemy\Phrasea\Notification\ReceiverInterface;

interface MailInterface
{
    /**
     * Returns
     *
     *  the Emitter
     *
     * @return EmitterInterface|null
     */
    public function getEmitter();

    /**
     * Sets an Emitter
     *
     * @param EmitterInterface|null $emitter
     *
     * @return MailInterface
     */
    public function setEmitter(EmitterInterface $emitter = null);

    /**
     * Returns the Receiver
     *
     * @return ReceiverInterface
     */
    public function getReceiver();

    /**
     * Sets the Receiver
     *
     * @param ReceiverInterface $receiver
     *
     * @return MailInterface
     */
    public function setReceiver(ReceiverInterface $receiver);

    /**
     * Returns the message for the link button
     *
     * @return string
     */
    public function getSubject();

    /**
     * Returns a text version of the e-mail
     *
     * @return string
     */
    public function getMessage();

    /**
     * Returns an HTML version of the e-mail
     *
     * @return string
     */
    public function renderHTML();

    /**
     * Returns the title of the Phraseanet Install
     *
     * @return string
     */
    public function getPhraseanetTitle();

    /**
     * Returns the absolute URL to Phraseanet Install
     *
     * @return string
     */
    public function getPhraseanetURL();

    /**
     * Returns an URL for a logo
     *
     * @return string
     */
    public function getLogoUrl();

    /**
     * Sets the URL for the logo
     *
     * @param string $url
     *
     * @return MailInterface
     */
    public function setLogoUrl($url);

    /**
     * Returns a alternate text for the logo
     *
     * @return string
     */
    public function getLogoText();

    /**
     * Sets the logo alternate text
     *
     * @param string $text
     *
     * @return MailInterface
     */
    public function setLogoText($text);

    /**
     * Returns an expiration date for the meaning of the message
     *
     * @return \DateTime
     */
    public function getExpirationMessage();

    /**
     * Sets an expiration date for the message meaning
     *
     * @param \DateTime|null $expiration
     *
     * @return MailInterface
     */
    public function setExpirationMessage(\DateTime $expiration = null);

    /**
     * Returns the message for the link button
     *
     * @return string
     */
    public function getButtonURL();

    /**
     * Sets a URL for the link button
     *
     * @return MailInterface
     */
    public function setButtonURL($url);
}
