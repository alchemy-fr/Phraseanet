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
     * @return EmitterInterface
     */
    public function getEmitter();

    /**
     * @return ReceiverInterface
     */
    public function getReceiver();

    /**
     * Gets the message for the link button
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
     * Returns a alternate text for the logo
     *
     * @return string
     */
    public function getLogoText();

    /**
     * Gets an expiration date for the meaning of the message
     *
     * @return \DateTime
     */
    public function getExpirationMessage();

    /**
     * Gets the message for the link button
     *
     * @return string
     */
    public function getButtonText();

    /**
     * Sets a URL for the link button
     *
     * @return MailInterface
     */
    public function getButtonURL();
}
