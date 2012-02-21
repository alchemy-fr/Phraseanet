<?php

abstract class eventsmanager_notifyAbstract extends eventsmanager_eventAbstract
{

  protected $events = array('__EVENT__');

  function fire($event, $params, &$object)
  {

  }

  abstract function datas($datas, $unread);

  function is_available()
  {
    return true;
  }

  function email()
  {
    return true;
  }

  abstract function icon_url();

  protected function get_prefs($class, $usr_id)
  {
    $user = User_Adapter::getInstance($usr_id, appbox::get_instance(\bootstrap::getCore()));

    return $user->getPrefs('notification_' . $class);
  }

}
