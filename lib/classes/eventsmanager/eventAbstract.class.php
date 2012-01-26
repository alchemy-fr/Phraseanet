<?php

abstract class eventsmanager_eventAbstract
{

  protected $events = array();
  protected $group = null;
  /**
   *
   * @var appbox
   */
  protected $appbox;
  /**
   *
   * @var registryInterface
   */
  protected $registry;
  /**
   *
   * @var \Alchemy\Phrasea\Core
   */
  protected $core;
  /**
   *
   * @var eventsmanager
   */
  protected $broker;


  public function __construct(appbox &$appbox, \Alchemy\Phrasea\Core $core, eventsmanager_broker &$broker)
  {
    $this->appbox = $appbox;
    $this->registry = $core->getRegistry();
    $this->core = $core;
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
