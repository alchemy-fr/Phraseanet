<?php
class httpRequest
{
//	private static $_FILTER_IMPLEMENTED = extension_loaded; 
//	
//	const VALIDATE_BOOLEAN	= FILTER_VALIDATE_BOOLEAN; 	
//	const VALIDATE_EMAIL	= FILTER_VALIDATE_EMAIL; 	
//	const VALIDATE_FLOAT	= FILTER_VALIDATE_FLOAT; 	
//	const VALIDATE_INT		= FILTER_VALIDATE_INT; 	
//	const VALIDATE_IP		= FILTER_VALIDATE_IP; 	
//	const VALIDATE_REGEXP	= FILTER_VALIDATE_REGEXP; 	
//	const VALIDATE_URL		= FILTER_VALIDATE_URL; 	
//	
//	const SANITIZE_EMAIL				= FILTER_SANITIZE_EMAIL;
//	const SANITIZE_ENCODED				= FILTER_SANITIZE_ENCODED;
//	const SANITIZE_MAGIC_QUOTES			= FILTER_SANITIZE_MAGIC_QUOTES;
//	const SANITIZE_NUMBER_FLOAT			= FILTER_SANITIZE_NUMBER_FLOAT;
//	const SANITIZE_NUMBER_INT			= FILTER_SANITIZE_NUMBER_INT;
//	const SANITIZE_SPECIAL_CHARS		= FILTER_SANITIZE_SPECIAL_CHARS;
//	const SANITIZE_STRING				= FILTER_SANITIZE_STRING;
//	const SANITIZE_STRIPPED				= FILTER_SANITIZE_STRIPPED;
//	const SANITIZE_URL					= FILTER_SANITIZE_URL;
	
	private static $_instance;
	protected $code;
	
	/**
	 * @return httpRequest
	 */
	public static function getInstance()
	{
		if(!(self::$_instance instanceof self))
		{
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	
	
	function __construct()
	{
		
	}
	
	public function is_ajax()
	{
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			return true;
		return false;
	}
	
	public function get_code()
	{
		if(!$this->code && isset($_SERVER['REDIRECT_STATUS']))
		{
			$this->code = $_SERVER['REDIRECT_STATUS'];
		}
		return $this->code;
	}
	
	public function set_code($code)
	{
		$this->code = (int)$code;
		return $this;
	}
	
	public function set_options()
	{
		
	}
	
	public function get_parms()
	{	
		$parm 	= array();
		$nargs 	= func_num_args();
			
		for($i = 0; $i < $nargs; $i++ )
		{
			$nom = func_get_arg($i);
			$parm[$nom] = isset($_GET[$nom]) ? $_GET[$nom] : (isset($_POST[$nom]) ? $_POST[$nom] : NULL);
		}
		return($parm);
	}

	public function has_post_datas()
	{
		return !empty($_POST);
	}

	public function get_post_datas()
	{
		return $_POST;
	}

	public function has_get_datas()
	{
		return !empty($_GET);
	}
	
	public function has_datas()
	{
		return ($this->has_post_datas() || $this->has_get_datas());
	}
	
	public function filter($data, $filter)
	{
		return filter_var($data, $filter);
	}
}