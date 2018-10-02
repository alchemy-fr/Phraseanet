<?php

namespace Alchemy\Phrasea\Core\Event\Databox;

use Symfony\Component\EventDispatcher\Event;

class DataboxEvent extends Event
{
    /** @var  \databox|null $databox */
    private $databox;

    /** @var array|null $args  supplemental parameters specific to an inherited event class */
    protected $args;

    /**
     * @param \databox|null $databox
     * @param array|null $args
     */
    public function __construct($databox, array $args = null)
    {
        $this->databox = $databox;
        $this->args = $args;
    }

    public function getDatabox()
    {
        return $this->databox;
    }
}
