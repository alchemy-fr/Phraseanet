<?php
class random
{
	
	function cleanTokens()
	{
		$conn = connection::getInstance();
		
		$date = new DateTime();
		$date = phraseadate::format_mysql($date);
		
		$sql = 'SELECT * FROM tokens WHERE expire_on < "'.$date.'" AND datas IS NOT NULL AND type="download"';
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				switch($row['type'])
				{
					case 'download':
						$file = GV_RootPath.'tmp/download/'.$row['value'].'.zip';
						if(is_file($file))
							unlink($file);
						break;
				}
			}
			$conn->free_result($rs);
		}
		$sql = 'UPDATE tokens SET datas=NULL WHERE expire_on < "'.$date.'"';
		$conn->query($sql);
		
		$date = new DateTime('-4 days');
		$date = phraseadate::format_mysql($date);
		
		$sql = 'DELETE FROM tokens WHERE expire_on < "'.$date.'"';
		$conn->query($sql);
	}
	
	public static function generatePassword ($length = 8)
	{
		$password = "";
		$possible = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
		$i = 0; 
		while ($i < $length) { 
			$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
			if (!strstr($password, $char)) { 
				$password .= $char;
				$i++;
			}
		}
		return $password;
	}
	
	public static function getUrlToken($type,$usr,$end_date=false,$datas='')
	{
		self::cleanTokens();
		$conn = connection::getInstance();
		$token = $test = false;
		if(!in_array($type,array('password','download', 'email', 'view', 'validate','rss')))
			return $token;
		if($conn)
		{
			$n = 1;
			
			while($n<100)
			{
				$test = self::generatePassword(16);
				if($rs = $conn->query('SELECT id FROM tokens WHERE value="'.$conn->escape_string($test).'"'))
				{
					if($conn->num_rows($rs) == 0)
					{
						if($conn->query('INSERT INTO tokens (id, value, type, usr_id, created_on, expire_on, datas) VALUES (null, "'.$conn->escape_string($test).'", "'.$conn->escape_string($type).'", "'.$conn->escape_string($usr ? $usr : '-1').'", NOW(), '.($end_date ? '"'.$conn->escape_string($end_date).'"' : 'null').', '.(trim($datas)!=''?('"'.$conn->escape_string($datas).'"'):'NULL').')'))
						{
							$token = $test;
							break;
						}
					}
				}
			}
		}
		return $token;
	}
	
	public static function removeToken($token)
	{
		self::cleanTokens();
		$conn = connection::getInstance();
		
		$sql = 'DELETE FROM tokens WHERE id="'.$conn->escape_string($token).'"';
		if($conn->query($sql))
			return true;
		return false;
	}
	
	public static function updateToken($token,$datas)
	{
		self::cleanTokens();
		$conn = connection::getInstance();
		
		$sql = 'UPDATE tokens SET datas="'.$conn->escape_string($datas).'" WHERE value="'.$conn->escape_string($token).'"';
		if($conn->query($sql))
			return true;
		return false;
	}
	
	public static function helloToken($token)
	{
		self::cleanTokens();
		$ret = false;
		
		$conn = connection::getInstance();
		
		if(!$conn)
			return $ret;
		
		$sql = 'SELECT * FROM tokens WHERE value="'.$conn->escape_string($token).'"';

		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$ret = $row;
			}
			$conn->free_result($rs);
		}
		return $ret;
	}
}