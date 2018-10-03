<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Application\Helper;

use Alchemy\Phrasea\Notification\Deliverer;
use Alchemy\Phrasea\Notification\Mail\MailInterface;

trait NotifierAware
{
    private $deliverer;

    /**
     * Set Locator to use to locate Deliverer
     *
     * @param callable $locator
     * @return $this
     */
    public function setDelivererLocator(callable $locator)
    {
        $this->deliverer = $locator;

        return $this;
    }

    /**
     * @return Deliverer
     */
    public function getDeliverer()
    {
        if ($this->deliverer instanceof Deliverer) {
            return $this->deliverer;
        }

        if (null === $this->deliverer) {
            throw new \LogicException('Locator was not set');
        }

        $instance = call_user_func($this->deliverer);
        if (!$instance instanceof Deliverer) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                Deliverer::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->deliverer = $instance;

        return $this->deliverer;
    }

    /**
     * @param MailInterface $mail
     * @param bool          $readReceipt
     * @param array         $attachments
     * @return int
     */
    public function deliver(MailInterface $mail, $readReceipt = false, array $attachments = null)
    {
        return $this->getDeliverer()->deliver($mail, $readReceipt, $attachments);
    }
}
