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

use Alchemy\Phrasea\Media\SubdefSubstituer;

trait SubDefinitionSubstituerAware
{
    private $subDefinitionSubstituer;

    /**
     * Set Locator to use to locate Border manager
     *
     * @param callable $locator
     * @return $this
     */
    public function setSubDefinitionSubstituerLocator(callable $locator)
    {
        $this->subDefinitionSubstituer = $locator;

        return $this;
    }

    /**
     * @return SubdefSubstituer
     */
    public function getSubDefinitionSubstituer()
    {
        if ($this->subDefinitionSubstituer instanceof SubdefSubstituer) {
            return $this->subDefinitionSubstituer;
        }

        if (null === $this->subDefinitionSubstituer) {
            throw new \LogicException('Sub definition substituer locator was not set');
        }

        $instance = call_user_func($this->subDefinitionSubstituer);
        if (!$instance instanceof SubdefSubstituer) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                SubdefSubstituer::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->subDefinitionSubstituer = $instance;

        return $this->subDefinitionSubstituer;
    }
}
