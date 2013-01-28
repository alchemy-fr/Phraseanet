<?php

abstract class eventsmanager_notifyAbstract extends eventsmanager_eventAbstract
{
    protected $events = array('__EVENT__');

    public function fire($event, $params, &$object)
    {

    }

    abstract public function datas($datas, $unread);

    public function is_available()
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
        $user = User_Adapter::getInstance($usr_id, $this->app);
        $pref = $user->getPrefs('notification_' . $class);

        return null !== $pref ? $pref : 1;
    }

    protected function shouldSendNotificationFor($usr_id)
    {
        return 0 !== (int) $this->get_prefs(get_class($this), $usr_id);
    }
}
