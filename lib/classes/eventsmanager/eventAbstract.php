<?php

use Alchemy\Phrasea\Application;

abstract class eventsmanager_eventAbstract
{
    protected $events = array();
    protected $group = null;

    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     * @var eventsmanager
     */
    protected $broker;

    public function __construct(Application $app, eventsmanager_broker $broker)
    {
        $this->app = $app;
        $this->broker = $broker;

        return $this;
    }

    public function get_group()
    {
        return $this->group;
    }

    public function get_events()
    {
        return $this->events;
    }

    abstract public function get_name();

    abstract public function fire($event, $params, &$object);
}
