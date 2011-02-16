<?php
class p4utils
{
	/*
	 * public getter : $o=new p4utils ; $prop=$o->Prop;
	 */
	public function __get($name)
	{
    	if(method_exists($this, ($f='__get'.$name)))
			return($this->$f());
		else
			trigger_error('Undefined method \''.$f.'\' in \''.__CLASS__.'\'');
	}

//	/*
//	 * public static call :  ; $prop=p4utils::getProp();
//	 */
//	public static function __callStatic($name, $arguments)
//    {
//    	if(method_exists(__CLASS__, ($f='__'.$name)))
//    		return(self::$f());
//		else
//			trigger_error('Undefined method \''.$f.'\' in \''.__CLASS__.'\'');
//	}
	
	
	public static function getSystem()
	{
		static $_system = NULL;
		if($_system === NULL)
		{
			$_system = strtoupper(php_uname('s'));
			if($_system == 'WINDOWS NT')		// patch NT
				$_system = 'WINDOWS';
		}
		return($_system);
	}
	
}