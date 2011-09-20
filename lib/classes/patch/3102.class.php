<?php 
class patch_3102 implements patch
{
	
	private $release = '3.1.20';
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
    
    $sql = 'UPDATE record SET sha256 = "" WHERE sha256 IS NULL AND parent_record_id = 0';
		$conn->query($sql);
		
		return true;
	}
}
