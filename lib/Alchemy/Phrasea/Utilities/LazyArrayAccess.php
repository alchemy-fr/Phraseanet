<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Utilities;

class LazyArrayAccess implements \ArrayAccess
{
    /**
     * @var callable
     */
    private $locator;

    public function __construct(callable $locator)
    {
        $this->locator = $locator;
    }

    public function offsetExists($offset)
    {
        return $this->fetchArrayAccessible()->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->fetchArrayAccessible()->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->fetchArrayAccessible()->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->fetchArrayAccessible()->offsetUnset($offset);
    }

    /**
     * @return \ArrayAccess
     */
    private function fetchArrayAccessible()
    {
        $locator = $this->locator;

        return $locator();
    }
}
