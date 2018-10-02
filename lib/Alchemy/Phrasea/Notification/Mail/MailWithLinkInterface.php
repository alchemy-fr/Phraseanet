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

interface MailWithLinkInterface
{
    /**
     * Set the URL for the link button
     *
     * @param string $url
     *
     * @return MailInterface
     */
    public function setButtonUrl($url);

    /**
     * Sets the expiration date for the meaning of the message
     *
     * @param \DateTime $expiration
     *
     * @return MailInterface
     */
    public function setExpiration(\DateTime $expiration);
}
