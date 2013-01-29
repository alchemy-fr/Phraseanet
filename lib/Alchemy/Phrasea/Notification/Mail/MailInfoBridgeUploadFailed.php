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

use Alchemy\Phrasea\Exception\LogicException;

class MailInfoBridgeUploadFailed extends AbstractMailWithLink
{
    /** @var string */
    private $adapter;
    /** @var string */
    private $reason;

    /**
     * Sets the adapter name
     *
     * @param string $adapter
     */
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Sets the reason
     *
     * @param string $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return sprintf(
            _('Upload failed on %s'),
            $this->getPhraseanetTitle()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (null === $this->adapter) {
            throw new LogicException('You must set an adapter before calling getMessage');
        }
        if (null === $this->reason) {
            throw new LogicException('You must set a reason before calling getMessage');
        }

        return sprintf(
            _('An upload on %s failed, the resaon is : %s'),
            $this->adapter,
            $this->reason
        );
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
