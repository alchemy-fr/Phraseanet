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
        return $this->app->trans('Upload failed on %application%', ['%application%' => $this->getPhraseanetTitle()], 'messages', $this->getLocale());
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

        return $this->app->trans('An upload on %bridge_adapter% failed, the resaon is : %reason%', ['%bridge_adapter%' => $this->adapter, '%reason%' => $this->reason], 'messages', $this->getLocale());
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
