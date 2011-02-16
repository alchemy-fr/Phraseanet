<?php 

class session
{
	private static $_instance;
	private static $_cli_storage = array();
	
	private static $_cli_usage = false;
	
	private static $_name = '';
	
	/**
	 * @return session
	 */
	public static function getInstance()
	{
		if(!self::$_instance)
		{
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	
	function getName()
	{
		return session_name();
	}
	
	function getId()
	{
		return session_id();
	}
	
	function __construct($cookie = 'system')
	{
		$sapi_name = strtolower(substr(php_sapi_name(), 0, 3));
		
		self::$_name = $cookie;
		
		if($sapi_name == 'cli')
		{
			self::$_cli_usage = true;
		}
		else
		{
			self::$_cli_usage = false;
			session_name($cookie);
			session_start();
		}
		
		$this->version = GV_version;
		
		$this->gfc_box = ($this->isset_cookie('gfc_box') && $this->get_cookie('gfc_box') == true) ? true : false;
		$this->analytics = GV_googleAnalytics;
	}
	
	function __set($key, $value)
	{
		if(self::$_cli_usage)
		{
			self::$_cli_storage[self::$_name][$key] = $value;
		}
		else
		{
			$_SESSION[$key] = $value;
		}
	}
	
	function __get($key)
	{
		if(self::$_cli_usage)
		{
			return self::$_cli_storage[self::$_name][$key];
		}
		else
		{
			return $_SESSION[$key];
		}
	}
	
	function __isset($key)
	{
		if(self::$_cli_usage)
		{
			return isset(self::$_cli_storage[self::$_name][$key]);
		}
		else
		{
			return isset($_SESSION[$key]);
		}
	}
	
	public function is_authenticated()
	{
		return (isset($this->ses_id) && isset($this->usr_id));
	}
	
	public function isset_cookie($name)
	{
		return isset($_COOKIE[$name]);
	}
	
	public function get_cookie($name)
	{
		return $_COOKIE[$name];
	}
	
	public function set_cookie($name, $value, $avalaibility, $http_only)
	{
		$https = false;
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])
			$https = true;
			
		$expire = $avalaibility === 0 ? 0 : time() + (int)$avalaibility;
		
		$http_only = !!$http_only;
		
		if($avalaibility >= 0)
			$_COOKIE[$name] = $value;
		else
			unset($_COOKIE[$name]);

		return setcookie($name, $value, $expire, '/', '', $https, $http_only);
	}
	
	public function destroy()
	{
		$this->set_cookie($this->getName(), '', 420000, false);
		session_destroy();	
		$this->set_cookie('last_act', '{}', 420000, true);
	}
}

