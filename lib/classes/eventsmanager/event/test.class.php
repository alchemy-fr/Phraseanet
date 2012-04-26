<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class eventsmanager_event_test extends eventsmanager_eventAbstract
{
    /**
     *
     * @var Array
     */
    protected $events = array('__EVENT__');

    /**
     *
     * @param string $event
     * @param Array $params
     * @param mixed content $object
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
