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

use Alchemy\Phrasea\Border\Manager;

trait BorderManagerAware
{
    private $borderManager;

    /**
     * Set Locator to use to locate Border manager
     *
     * @param callable $locator
     * @return $this
     */
    public function setBorderManagerLocator(callable $locator)
    {
        $this->borderManager = $locator;

        return $this;
    }

    /**
     * @return Manager
     */
    public function getBorderManager()
    {
        if ($this->borderManager instanceof Manager) {
            return $this->borderManager;
        }

        if (null === $this->borderManager) {
            throw new \LogicException('DeliverDataInterface locator was not set');
        }

        $instance = call_user_func($this->borderManager);
        if (!$instance instanceof Manager) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                Manager::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->borderManager = $instance;

        return $this->borderManager;
    }
}
