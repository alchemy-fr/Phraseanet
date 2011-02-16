<?php
abstract class event
{
	protected $events = array();
	
	protected $group = null;
	
	public function __construct()
	{
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

	abstract public function fire($event,$params,&$object);
}