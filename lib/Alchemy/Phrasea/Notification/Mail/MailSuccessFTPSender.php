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

use Alchemy\Phrasea\Exception\LogicException;

class MailSuccessFTPSender extends AbstractMail
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

        return $this->app->trans('task::ftp:Status about your FTP transfert from %application% to %server%', [
            '%application%' => $this->getPhraseanetTitle(),
            '%server%'      => $this->server,
        ]);
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
