<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class eventsmanager_notifyAbstract extends eventsmanager_eventAbstract
{
    protected $events = ['__EVENT__'];

    public function fire($event, $params, &$object)
    {

    }

    abstract public function datas($datas, $unread);

    public function is_available($usr_id)
    {
        return true;
    }

    public function email()
    {
        return true;
    }

    abstract public function icon_url();

    protected function get_prefs($class, $usr_id)
    {
        $user = $this->app['manipulator.user']->getRepository()->find($usr_id);

        return $user->getNotificationSettingValue($class);
    }

    protected function shouldSendNotificationFor($usr_id)
    {
        return 0 !== (int) $this->get_prefs(get_class($this), $usr_id);
    }
}
