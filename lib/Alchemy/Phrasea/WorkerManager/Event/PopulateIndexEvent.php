<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class PopulateIndexEvent extends SfEvent
{
    /** @var array */
    private $data;

    /**
     * PopulateIndexEvent constructor.
     *  data an array of host, port, indexName, databoxIds(array)
     * @param $data
     */
    public function __construct($data)
    {
        $this->data     = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

}
