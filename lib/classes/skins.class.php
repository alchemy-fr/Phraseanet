<?php
class skins
{
	public static function merge($dir='')
	{
		$path_in = dirname( __FILE__ ) . '/../../lib/skins/';
		$path_cs = dirname( __FILE__ ) . '/../../config/skins/';
		$path_out = dirname( __FILE__ ) . '/../../www/skins/';
		
		
		if(is_dir($path_cs.$dir))
		{
			if($hdir = opendir($path_cs.$dir))
			{
				while(false !== ($file = readdir($hdir)))
				{
					if(substr($file,0,1)=="." || mb_strtolower($file)==".cvs" || mb_strtolower($file)==".svn" || mb_strtolower($file)==".htaccess")
						continue;
						
					$current_dir = ($dir != '' ? $dir. '/' : '') .$file;
					
					if(is_dir($path_cs . $current_dir))
					{
						if(p4::fullmkdir($path_out . $current_dir))
						{
							self::merge($current_dir);
						}
					}
					if(is_file($path_cs . $current_dir))
					{
							copy($path_cs.$current_dir,$path_out.$current_dir);
					}
				}
			}
		}
		if(is_dir($path_in.$dir))
		{
			if($hdir = opendir($path_in.$dir))
			{
				while(false !== ($file = readdir($hdir)))
				{
					if(substr($file,0,1)=="." || mb_strtolower($file)==".cvs" || mb_strtolower($file)==".svn" || mb_strtolower($file)==".htaccess")
						continue;
						
					$current_dir = ($dir != '' ? $dir. '/' : '') .$file;
					
					if(is_dir($path_in . $current_dir))
					{
						if(!is_dir($path_out . $current_dir))
						{
							p4::fullmkdir($path_out . $current_dir);
						}
						self::merge($current_dir);
					}
					if(is_file($path_in . $current_dir))
					{
						if(!is_file($path_out.$current_dir))
							copy($path_in.$current_dir,$path_out.$current_dir);
					}
				}
			}
		}
	}
	
	public static function delete_skins_files()
	{
		$origine = GV_RootPath.'www/skins/';
		p4::empty_directory($origine);
	}
}