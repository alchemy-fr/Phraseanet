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

use Alchemy\Phrasea\Http\DeliverDataInterface;
use Symfony\Component\HttpFoundation\Response;

trait DelivererAware
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
     * @return DeliverDataInterface
     */
    public function getDeliverer()
    {
        if ($this->deliverer instanceof DeliverDataInterface) {
            return $this->deliverer;
        }

        if (null === $this->deliverer) {
            throw new \LogicException('DeliverDataInterface locator was not set');
        }

        $instance = call_user_func($this->deliverer);
        if (!$instance instanceof DeliverDataInterface) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                DeliverDataInterface::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->deliverer = $instance;

        return $this->deliverer;
    }

    /**
     * Returns a HTTP Response ready to deliver a binary file
     *
     * @param string $file
     * @param string $filename
     * @param string $disposition
     * @param string|null $mimeType
     * @param integer $cacheDuration
     * @return Response
     */
    public function deliverFile($file, $filename = null, $disposition = DeliverDataInterface::DISPOSITION_INLINE, $mimeType = null, $cacheDuration = null)
    {
        return $this->getDeliverer()->deliverFile($file, $filename, $disposition, $mimeType, $cacheDuration);
    }
}
