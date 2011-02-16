<?php
class server
{
	private $_server_software;
	
	function __construct()
	{
		$this->_server_software = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower($_SERVER['SERVER_SOFTWARE']) : "";
	}
	
	public function is_nginx()
	{
		if(strpos($this->_server_software, 'nginx') !== false)
			return true;
		return false;
	}
	
	public function is_lighttpd()
	{
		if(strpos($this->_server_software, 'lighttpd') !== false)
			return true;
		return false;
	}
	
	public function is_apache()
	{
		if(strpos($this->_server_software, 'apache') !== false)
			return true;
		return false;
	}
}