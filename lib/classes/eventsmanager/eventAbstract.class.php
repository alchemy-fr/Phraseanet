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
   * @var eventsmanager
   */
  protected $broker;


  public function __construct(appbox &$appbox, registryInterface $registry, eventsmanager_broker &$broker)
  {
    $this->appbox = $appbox;
    $this->registry = $registry;
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
