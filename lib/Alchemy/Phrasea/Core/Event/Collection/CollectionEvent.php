<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Collection;

use Symfony\Component\EventDispatcher\Event;

abstract class CollectionEvent extends Event
{
    /** @var \collection $collection */
    private $collection;
    /** @var  array|null $args */
    protected $args;

    /**
     * @param \collection|null $collection
     * @param array|null $args
     */
    public function __construct($collection, array $args = null)
    {
        $this->collection = $collection;
        $this->args = $args;
    }

    /**
     * @return \collection|null
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
