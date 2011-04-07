<?php
class collection
{
	private $base_id = false;
	private $sbas_id = false;
	private $coll_id = false;
	
	private static $_logos = array();
	private static $_names = array();
	private static $_stamps = array();
	private static $_watermarks = array();
	private static $_presentations = array();
	
	private static $_collections = array();
	
	public function __construct($base_id)
	{
		$conn = connection::getInstance();
		
		$sql = 'SELECT server_coll_id, sbas_id, base_id FROM bas WHERE base_id = "'.$conn->escape_string($base_id).'"';
		
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$this->coll_id = $row['server_coll_id'];
				$this->base_id = $row['base_id'];
				$this->sbas_id = $row['sbas_id'];
				
				return true;
			}
		}
		return false;
	}
	
	public static function create_collection($sbas_id, $name)
	{
	
		$connbas = connection::getInstance($sbas_id);
		$conn = connection::getInstance();
		$session = session::getInstance();
	
		if(!$connbas || !$conn)
		{
			throw new Exception('Impossible de se connecter a la base');
		}
		$prefs = '<?xml version="1.0" encoding="UTF-8"?>
			<baseprefs>
				<status>0</status>
				<sugestedValues>
				</sugestedValues>
			</baseprefs>';
			
		$new_id = $connbas->getId("COLL");
			
		$sql = "INSERT INTO coll (coll_id, htmlname, asciiname, prefs, logo) 
				VALUES ('".$connbas->escape_string($new_id)."', '" . $connbas->escape_string($name) . "'
					, '" . $connbas->escape_string($name) . "', '" . $connbas->escape_string($prefs) . "', '')" ;
			
		if(!$connbas->query($sql))
		{
			throw new Exception('Impossible de se creer la collection');
		}	

		$new_bas = $conn->getId("BAS");
		
		$rowbas = array();
		$sql = 'SELECT * FROM sbas WHERE sbas_id="'.$conn->escape_string($sbas_id).'"';
		if($rs = $conn->query($sql))
		{
			$rowbas = $conn->fetch_assoc($rs);
			$conn->free_result($rs);
		}
		
		$fn = $fv = "";
		$fn .= "base_id"            ; $fv .= $new_bas;
		$fn .= ", active"           ; $fv .= ", 1";
		$fn .= ", server_coll_id"   ; $fv .= ", "  . $new_id;
		$fn .= ", sbas_id"           ; $fv .= ", "  . $sbas_id;
		$fn .= ", aliases";   $fv .= ",''";
		
		$sql = "INSERT INTO bas (".$fn.") VALUES (".$fv.")";
			
		if(!$conn->query($sql))
		{
			throw new Exception('Impossible de se creer la collection');
		}	
		$cache_appbox = cache_appbox::getInstance();
		$cache_appbox->delete('list_bases');
		cache_databox::update($sbas_id,'structure');
		
		self::set_admin($new_bas, $session->usr_id);
		return $new_bas;
	}
	
	public function set_admin($base_id, $usr_id)
	{
		$conn = connection::getInstance();
		
		try {
			$fields = array(
				"base_id"			=> (int)$base_id,
				"usr_id"			=> (int)$usr_id,
				"canpreview"		=> "'1'",
				"canhd"				=> "'1'",
				"canputinalbum"		=> "'1'",
				"candwnldhd"		=> "'1'",
				"candwnldpreview"	=> "'1'",
				"cancmd"			=> "'1'",
				"canadmin"			=> "'1'",
				"actif"				=> "'1'",
				"canreport"			=> "'1'",
				"canpush"			=> "'1'",
				"basusr_infousr"	=> "''",
				"canaddrecord"		=> "'1'",
				"canmodifrecord"	=> "'1'",
				"candeleterecord"	=> "'1'",
				"chgstatus"			=> "'1'",
				"imgtools"			=> "'1'",
				"manage"			=> "'1'",
				"modify_struct"		=> "'1'",
				"bas_manage"		=> "'1'",
				"creationdate"		=> 'NOW()'
			);
			
			$sql = 'INSERT INTO basusr ('.implode(', ',array_keys($fields)).') VALUES ('.implode(', ',$fields).')';
			$conn->query($sql);
			
			$cache_user = cache_user::getInstance();
			$cache_user->delete($usr_id);
		}
		catch(Exception $e)
		{
			return false;
		}
		return true;
	}

	public static function mount_collection($sbas_id, $coll_id)
	{
		$connbas = connection::getInstance($sbas_id);
		$conn = connection::getInstance();
		$session = session::getInstance();

		if(!$connbas || !$conn)
		{
			throw new Exception('Impossible de se connecter a la base');
		}

		$new_bas = $conn->getId("BAS");

		$fn = $fv = "";
		$fn .= "base_id"            ; $fv .= $new_bas;
		$fn .= ", active"           ; $fv .= ", 1";
		$fn .= ", server_coll_id"   ; $fv .= ", "  . $coll_id;
		$fn .= ", sbas_id"           ; $fv .= ", "  . $sbas_id;
		$fn .= ", aliases";   $fv .= ",''";

		$sql = "INSERT INTO bas (".$fn.") VALUES (".$fv.")";

		if(!$conn->query($sql))
		{
			throw new Exception('Impossible de mounter la collection');
		}
		$cache_appbox = cache_appbox::getInstance();
		$cache_appbox->delete('list_bases');
		cache_databox::update($sbas_id,'structure');

		self::set_admin($new_bas, $session->usr_id);
		return $new_bas;
	}


	public static function activate_collection($sbas_id, $base_id)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();

		if(!$conn)
		{
			throw new Exception('Impossible de se connecter a la base');
		}

		$sql = "UPDATE bas SET active='1' WHERE base_id = '".$conn->escape_string($base_id)."'" ;

		if(!$conn->query($sql))
		{
			throw new Exception('Impossible dactiver la collection');
		}
		$cache_appbox = cache_appbox::getInstance();
		$cache_appbox->delete('list_bases');
		cache_databox::update($sbas_id,'structure');

		return true;
	}
	
	public static function duplicate_right_from_bas($base_id_from, $base_id_dest)
	{
		$conn = connection::getInstance();
		
		try {
			$sql = 'SELECT * FROM basusr WHERE base_id="'.$conn->escape_string($base_id_from).'"';
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$fields = array(
						"base_id"			=> (int)$base_id_dest,
						"usr_id"			=> "'".$row["usr_id"]."'",
						"canpreview"		=> "'".$row["canpreview"]."'",
						"canhd"				=> "'".$row["canhd"]."'",
						"canputinalbum"		=> "'".$row["canputinalbum"]."'",
						"candwnldhd"		=> "'".$row["candwnldhd"]."'",
						"candwnldpreview"	=> "'".$row["candwnldpreview"]."'",
						"cancmd"			=> "'".$row["cancmd"]."'",
						"canadmin"			=> "'".$row["canadmin"]."'",
						"actif"				=> "'".$row["actif"]."'",
						"canreport"			=> "'".$row["canreport"]."'",
						"canpush"			=> "'".$row["canpush"]."'",
						"creationdate"		=> 'NOW()',
						"mask_and"			=> "'".$row["mask_and"]."'",
						"mask_xor"			=> "'".$row["mask_xor"]."'",
						"restrict_dwnld"	=> "'".$row["restrict_dwnld"]."'",
						"month_dwnld_max"	=> "'".$row["month_dwnld_max"]."'",
						"remain_dwnld"		=> "'".$row["remain_dwnld"]."'",
						"time_limited"		=> "'".$row["time_limited"]."'",
						"limited_from"		=> "'".$row["limited_from"]."'",
						"limited_to"		=> "'".$row["limited_to"]."'",
						"lastconn"			=> "'0000-00-00 00:00:00'",
						"needwatermark"		=> "'".$row["needwatermark"]."'",
						"canaddrecord"		=> "'".$row["canaddrecord"]."'",
						"canmodifrecord"	=> "'".$row["canmodifrecord"]."'",
						"candeleterecord"	=> "'".$row["candeleterecord"]."'",
						"chgstatus"			=> "'".$row["chgstatus"]."'",
						"imgtools"			=> "'".$row["imgtools"]."'",
						"manage"			=> "'".$row["manage"]."'",
						"modify_struct"		=> "'".$row["modify_struct"]."'",
						"bas_manage"		=> "'".$row["bas_manage"]."'"
					);
					
					$sql = 'INSERT INTO basusr ('.implode(', ', array_keys($fields)).') VALUES ('.implode(', ', $fields).')';
					
					$conn->query($sql);
				}
				$conn->free_result($rs);
			}
		}
		catch(Exception $e)
		{
			echo 'exception : '.$e->getMessage().' <br>';
		}
	}
	
	public static function get($base_id)
	{
	
		if(!isset(self::$_collections[$base_id]))
		{
			self::$_collections[$base_id] = new collection($base_id);
		}
		
		return self::$_collections[$base_id];
	}
	
	public function __destruct()
	{
		
	}

	public static function getLogo($base_id,$printname = false)
	{
		$base_id_key =  $base_id . '_' . ($printname ? '1' : '0');
		
		if(!isset(self::$_logos[$base_id_key]))
		{
			if(is_file( GV_RootPath . 'config/minilogos/'.$base_id ))
			{
				$name = phrasea::bas_names($base_id);
				self::$_logos[$base_id_key] = '<img title="'.$name.'" src="/minilogos/' . $base_id.'" />';
			}
			elseif($printname)
			{
				self::$_logos[$base_id_key] = phrasea::bas_names($base_id);
			}
		}
		 
		return isset(self::$_logos[$base_id_key]) ? self::$_logos[$base_id_key] : '';
	}
	
	public static function getName($base_id)
	{
		
		if(!isset(self::$_names[$base_id]))
		{
			$lb = phrasea::bases();
			foreach($lb['bases'] as $base)
				foreach($base['collections'] as $coll)
					if($coll['base_id'] == $base_id)
						self::$_names[$base_id] = $coll['name'];
		}
		
		return isset(self::$_names[$base_id]) ? self::$_names[$base_id] : '';
	}
	
	public static function printLogo($base_id)
	{
		$cache_coll = cache_collection::getInstance();
		
		if(($tmp = $cache_coll->get($base_id,'logo')) !== false)
			return $tmp;
		
		$filename = GV_RootPath.'config/minilogos/'.$base_id;
		
		$out = '';
		
		if(is_file($filename))
		{
			$out = file_get_contents($filename);
		}
		
		$cache_coll->set($base_id,'logo', $out);
		
		return $out;
	}
	
	public static function getWatermark($base_id)
	{
		if(!isset(self::$_watermarks['base_id']))
		{
			if(is_file( GV_RootPath . 'config/wm/'.$base_id ))
				self::$_watermarks['base_id'] = '<img src="/watermark/' . $base_id.'" />';
		}
		 
		return isset(self::$_watermarks['base_id']) ? self::$_watermarks['base_id'] : '';
	}
	
	public static function printWatermark($base_id)
	{
		
		$cache_coll = cache_collection::getInstance();
		
		if(($tmp = $cache_coll->get($base_id,'watermark')) !== false)
			return $tmp;
		
		$filename = GV_RootPath.'config/wm/'.$base_id;
		
		$out = '';
		
		if(is_file($filename))
		{
			$out = file_get_contents($filename);
		}
		
		$cache_coll->set($base_id,'watermark', $out);
		
		return $out;
	}
	
	
	public static function getPresentation($base_id)
	{
		if(!isset(self::$_presentations['base_id']))
		{
			if(is_file( GV_RootPath . 'config/presentation/'.$base_id ))
				self::$_presentations['base_id'] = '<img src="/presentation/' . $base_id.'" />';
		}
		 
		return isset(self::$_presentations['base_id']) ? self::$_presentations['base_id'] : '';
	}
	
	public static function printPresentation($base_id)
	{
		$cache_coll = cache_collection::getInstance();
		
		if(($tmp = $cache_coll->get($base_id,'presentation')) !== false)
			return $tmp;
			
		$filename = GV_RootPath.'config/presentation/'.$base_id;
		
		$out = '';
		
		if(is_file($filename))
		{
			$out = file_get_contents($filename);
		}
		
		$cache_coll->set($base_id,'presentation', $out);
		
		return $out;
	}
	
	
	public static function getStamp($base_id)
	{
		if(!isset(self::$_stamps['base_id']))
		{
			if(is_file( GV_RootPath . 'config/stamp/'.$base_id ))
				self::$_stamps['base_id'] = '<img src="/stamp/' . $base_id.'" />';
		}
		 
		return isset(self::$_stamps['base_id']) ? self::$_stamps['base_id'] : '';
	}
	
	public static function printStamp($base_id)
	{
		$cache_coll = cache_collection::getInstance();
		
		if(($tmp = $cache_coll->get($base_id,'stamp')) !== false)
			return $tmp;
			
		$filename = GV_RootPath.'config/stamp/'.$base_id;
		
		$out = '';
		
		if(is_file($filename))
		{
			$out = file_get_contents($filename);
		}
		
		$cache_coll->set($base_id,'stamp', $out);
		
		return $out;
	}
}