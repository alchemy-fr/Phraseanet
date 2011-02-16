<?php
class event_test extends event
{
	protected $events = array('__EVENT__');
	
	public function fire($event,$params,&$object)
	{
		return;
	}
}