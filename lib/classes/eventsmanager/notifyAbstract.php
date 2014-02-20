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

    protected function shouldSendNotificationFor($usrId)
    {
        $user = $this->app['manipulator.user']->getRepository()->find($usrId);

        return $this->app['settings']->getUserNotificationSetting($user, get_class($this));
    }
}
