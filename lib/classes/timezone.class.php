<?php
class timezone
{
	
	public static function getForm($props=array(),$selected=false)
	{
		$form = '<select ';
		
		foreach($props as $k=>$v)
			$form .= $k.'="'.$v.'" ';
		$form .='>';
		
		$list = self::getList();
		
		$times = array();
		
		foreach($list as $k=>$v)
		{
			foreach($v as $v2)
				if(($timezone = trim($v2['timezone_id'])) !== '')
					$times[] = $timezone;
		}

		$times = array_unique($times);
		asort($times);
		
		foreach($times as $time)
			$form .= '<option '.($selected == $time ? "selected" : "").' value="'.$time.'">'.$time.'</option>';
				
		$form .= '</select>';
		
		return $form;
	}
	
	private static function getList()
	{
		return DateTimeZone::listAbbreviations();
	}
	
}