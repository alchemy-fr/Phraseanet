<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Collection;

use Symfony\Component\EventDispatcher\Event;

abstract class CollectionEvent extends Event
{
    private $collection;

    public function __construct(\collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return \collection
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
