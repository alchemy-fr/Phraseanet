<?php
class exiftool
{
	
	
	public static function get_fields($filename, $fields)
	{
		$system = p4utils::getSystem();
		
		$ret = array();
		
		if(in_array($system, array('DARWIN', 'LINUX')))
		{
			$cmd = GV_exiftool.' ' . escapeshellarg($filename) . '';
		}
		else
		{
			if(chdir(GV_RootPath.'tmp/'))
			{
				$cmd = 'start /B /LOW ' . GV_exiftool.' ' . escapeshellarg($filename) . '';
			}
		}
		if($cmd)
		{
			$s = @shell_exec($cmd);
			if(trim($s) != '')
			{
				$lines = explode("\n", $s);
				
				foreach($lines as $line)
				{
					$cells = explode(':', $line);
					
					if(count($cells) < 2 )
						continue;
						
					$cell_1 = trim(array_shift($cells));
					$cell_2 = trim(implode(':', $cells));
					
					if(in_array($cell_1, $fields))
						$ret[$cell_1] = $cell_2;
				}
				
			}
		}
		
		foreach($fields as $field)
		{
			if(!isset($ret[$field]))
				$ret[$field] = false;
		}
		
		return $ret;
	}
	
	
}