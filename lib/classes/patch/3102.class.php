<?php 
class patch_3102 implements patch
{
	
	private $release = '3.1.0';
	private $concern = array('application_box');
	
	function get_release()
	{
		return $this->release;
	}
	
	function concern()
	{
		return $this->concern;
	}
	
	function apply($id)
	{
		$conn = connection::getInstance();
		
		$task_id = $conn->getId('task');
		
		$sql = 'INSERT INTO `task2` 
				(`task_id`, `usr_id_owner`, `pid`, `status`, `crashed`, `active`, `name`, `last_exec_time`, `class`, `settings`, `completed`) 
				VALUES 
				("'.$conn->escape_string($task_id).'", 0, 0, "stopped", 0, 1, "upgrade to v3.1", "0000-00-00 00:00:00", "task_upgradetov31", 
					"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<tasksettings>\r\n</tasksettings>", -1)';
		
		$conn->query($sql);
		
	
		if(!function_exists('phrasea_uuid_create'))
		{
			echo "<div style='padding:5px;background-color:red;color:black;'>".
				sprintf(_('Attention, la fonction %s est indisponible, vous devriez mettre a jour lextension phrasea dans sa derniere version'), 'phrasea_uuid_create').
				"</div>";
		}
		if(!function_exists('phrasea_uuid_is_valid'))
		{
			echo "<div style='padding:5px;background-color:red;color:black;'>".
			sprintf(_('Attention, la fonction %s est indisponible, vous devriez mettre a jour lextension phrasea dans sa derniere version'), 'phrasea_uuid_is_valid').
			"</div>";
		}
		if(!function_exists('phrasea_uuid_compare'))
		{
			echo "<div style='padding:5px;background-color:red;color:black;'>".
			sprintf(_('Attention, la fonction %s est indisponible, vous devriez mettre a jour lextension phrasea dans sa derniere version'), 'phrasea_uuid_compare').
			"</div>";
		}
		return true;
	}
}
