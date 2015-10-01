<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectionRelated extends Event
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
