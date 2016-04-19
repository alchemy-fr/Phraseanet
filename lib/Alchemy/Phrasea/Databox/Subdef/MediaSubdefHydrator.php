<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Subdef;

use Alchemy\Phrasea\Hydration\Hydrator;
use Assert\Assertion;

class MediaSubdefHydrator implements Hydrator
{
    /**
     * @param \media_subdef $instance
     * @param array $data
     * @throws \Assert\AssertionFailedException
     */
    public function hydrate($instance, array $data)
    {
        Assertion::isInstanceOf($instance, \media_subdef::class);

        $closure = \Closure::bind(function (array $data) {
            $this->loadFromArray($data);
        }, $instance, \media_subdef::class);

        $closure($data);
    }

    /**
     * @param \media_subdef $instance
     * @return array
     * @throws \Assert\AssertionFailedException
     */
    public function extract($instance)
    {
        Assertion::isInstanceOf($instance, \media_subdef::class);

        $closure = \Closure::bind(function () {
            return $this->toArray();
        }, $instance, \media_subdef::class);

        return $closure();
    }
}
