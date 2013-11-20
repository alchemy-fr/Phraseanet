<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

abstract class eventsmanager_eventAbstract
{
    protected $events = [];
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
