<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class eventsmanager_event_test extends eventsmanager_eventAbstract
{
    /**
     *
     * @var Array
     */
    protected $events = ['__EVENT__'];

    /**
     *
     * @param  string        $event
     * @param  Array         $params
     * @param  mixed content $object
     * @return event_test
     */
    public function fire($event, $params, &$object)
    {
        return $this;
    }

    public function get_name()
    {
        return 'Test event';
    }
}
