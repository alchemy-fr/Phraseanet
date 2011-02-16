<?php
abstract class notify extends event
{
	protected $events = array('__EVENT__');
	
	function fire($event,$params,&$object)
	{
		
	}
	
	abstract function datas($datas, $unread);

	function is_avalaible()
	{
		return true;
	}
	
	function email()
	{
		return true;
	}
	
	abstract function icon_url();
	
}