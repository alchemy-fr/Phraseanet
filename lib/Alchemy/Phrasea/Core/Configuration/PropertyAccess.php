<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

/**
 * Give recursive access to a ConfigurationInterface
 *
 * @example PropertyAccess::get(['level1', 'level2']) return the value of $conf['level1']['level2']
 */
class PropertyAccess
{
    /** @var ConfigurationInterface */
    private $conf;

    public function __construct(ConfigurationInterface $conf)
    {
        $this->conf = $conf;
    }

    /**
     * Gets the value given one or more properties
     *
     * @param array|string $props   The property to get
     * @param mixed        $default The default value to return in case the property is not accessible
     *
     * @return mixed
     */
    public function get($props, $default = null)
    {
        return \igorw\get_in($this->conf->getConfig(), $this->arrayize($props), $default);
    }

    /**
     * Checks if a property exists.
     *
     * @param array|string $props The property to check
     *
     * @return Boolean
     */
    public function has($props)
    {
        $current = $this->conf->getConfig();
        $props = $this->arrayize($props);

        foreach ($props as $prop) {
            if (!isset($current[$prop])) {
                return false;
            }

            $current = $current[$prop];
        }

        return true;
    }

    /**
     * Set a value to a property
     *
     * @param array|string $props The property to set
     * @param mixed        $value
     *
     * @return mixed The set value
     */
    public function set($props, $value)
    {
        $this->conf->setConfig(\igorw\assoc_in($this->conf->getConfig(), $this->arrayize($props), $value));

        return $value;
    }

    /**
     * Merges a value to the current property value
     *
     * @param array|string $props The property to set
     * @param array        $value
     *
     * @throws InvalidArgumentException If the target property contains a scalar.
     *
     * @return mixed The merged value
     */
    public function merge($props, array $value)
    {
        $conf = $this->conf->getConfig();
        $ret = $this->doMerge($conf, $this->arrayize($props), $value);
        $this->conf->setConfig($conf);

        return $ret;
    }

    /**
     * Removes a property
     *
     * @param array|string $props The property to remove
     *
     * @return mixed The value of the removed property
     */
    public function remove($props)
    {
        $conf = $this->conf->getConfig();
        $value = $this->doRemove($conf, $this->arrayize($props));
        $this->conf->setConfig($conf);

        return $value;
    }

    private function doMerge(array &$conf, array $props, array $value)
    {
        $prop = array_shift($props);
        if (count($props) === 0) {
            if (array_key_exists($prop, $conf)) {
                if (!is_array($conf[$prop])) {
                    throw new InvalidArgumentException('Unable to merge an array in a scalar.');
                }

                return $conf[$prop] = array_replace($conf[$prop], $value);
            }

            return $conf[$prop] = $value;
        }
        if (!array_key_exists($prop, $conf)) {
            $conf[$prop] = [];
        }

        return $this->doMerge($conf[$prop], $props, $value);
    }

    private function doRemove(array &$conf, array $props)
    {
        $prop = array_shift($props);
        if (count($props) === 0) {
            if (array_key_exists($prop, $conf)) {
                $value = $conf[$prop];
            } else {
                $value = null;
            }
            unset($conf[$prop]);

            return $value;
        }

        if (array_key_exists($prop, $conf)) {
            return $this->doRemove($conf[$prop], $props);
        }
    }

    private function arrayize($value)
    {
        if (!is_array($value)) {
            return [$value];
        }

        return $value;
    }
}
