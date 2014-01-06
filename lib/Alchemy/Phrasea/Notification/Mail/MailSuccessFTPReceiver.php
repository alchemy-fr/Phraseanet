<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailSuccessFTPReceiver extends AbstractMail
{
    /** @var string */
    private $server;

    /**
     * Sets the server related to the FTP export
     *
     * @param string $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->server) {
            throw new LogicException('You must set server before calling getSubject');
        }

        return sprintf(
            _('You just received some documents from %s on %s'),
            $this->getPhraseanetTitle(), $this->server
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
    }
}
