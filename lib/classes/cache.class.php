<?php


class cache
{
	protected $memcached = false;
	protected static $_instance = false;
	
	protected $extension_name;

	function __construct()
	{
		$this->memcached = false;
		if(!defined('GV_use_cache') || GV_use_cache !== true)
		{
			return false;
		}
		if(!defined('GV_memcached') || !defined('GV_memcached_port'))
		{		
			return false;
		}
		if(extension_loaded('Memcached') && ($this->memcached = new Memcached()) != false)
		{
			if(($this->memcached->addServer(GV_memcached,GV_memcached_port)) != false)
			{
				if((($version = $this->memcached->getVersion()) != false) && isset($version[GV_memcached.':'.GV_memcached_port]))
				{
					$version = $version[GV_memcached.':'.GV_memcached_port];
//					if(version_compare($version,'1.3', '>='))
//					{
//						$this->memcached->setOption(Memcached::OPT_BINARY_PROTOCOL,true);
//					}
					
					$this->memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, 500);
					$this->memcached->setOption(Memcached::OPT_SEND_TIMEOUT, 500);
					$this->memcached->setOption(Memcached::OPT_RECV_TIMEOUT, 500);
					$this->memcached->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 1); 
					$this->memcached->setOption(Memcached::OPT_DISTRIBUTION,Memcached::DISTRIBUTION_CONSISTENT); 
				}
				if($this->memcached->getStats())
				{
					$this->extension_name = 'memcached';
					return $this->memcached;
				}
			}
		}
		elseif(extension_loaded('Memcache') && ($this->memcached = new Memcache()) != false)
		{
			if(($this->memcached->addServer(GV_memcached,GV_memcached_port)) != false)
			{
				if($this->memcached->getServerStatus(GV_memcached,GV_memcached_port))
				{
					$this->extension_name = 'memcache';
					return $this->memcached;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * @return cache
	 */
	public static function getInstance()
	{
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	function getExtensionName()
	{
		return $this->extension_name;
	}
	
	function is_ok()
	{
		if(!$this->memcached)
			return false;
			
		if(!$this->memcached->getStats())
			return false;
			
		return true;
	}

	function set($key,$value,$expiration=604800)
	{
		if(!$this->memcached)
			return false;
		
		if(defined('GV_debug') && GV_debug)
		{
			$error = '--> SET DE CACHE  `'.$key."` '\n";
			file_put_contents(GV_RootPath.'logs/cache.log',$error,FILE_APPEND);
			logs::rotate(GV_RootPath.'logs/cache.log');
		}
		
		
		if(get_class($this->memcached) == 'Memcache')
		{
			return $this->memcached->set($key,$value,MEMCACHE_COMPRESSED,$expiration);
		}
		if(get_class($this->memcached) == 'Memcached')
		{
			return $this->memcached->set($key,$value,$expiration);
		}
		
		return false;
	}

	function get($key)
	{
		
		if(!$this->memcached)
			return false;
	
//		if(defined('GV_debug') && GV_debug)
//		{
//			if(strpos($key,'basket') !== false)
//			{
//				$error = ' GET DE CACHE  `'.$key."` '\n";
//				file_put_contents(GV_RootPath.'logs/cache.log',$error,FILE_APPEND);
//				logs::rotate(GV_RootPath.'logs/cache.log');
//			}
//		}

		return $this->memcached->get($key);
	}
	
	function delete($key)
	{
	
		if(!$this->memcached)
			return false;
		
		if(defined('GV_debug') && GV_debug)
		{
			$error = '--> LEVEE DE CACHE  `'.$key."` '\n";
			file_put_contents(GV_RootPath.'logs/cache.log',$error,FILE_APPEND);
			logs::rotate(GV_RootPath.'logs/cache.log');
		}
		
		return $this->memcached->delete($key);
	}
	
	function deleteMulti($array_keys)
	{
		
		if(!$this->memcached)
			return false;
		
		foreach($array_keys as $key)
			$this->memcached->delete($key);
			
		return true;
	}

	function getStats()
	{
		
		if(!$this->memcached)
			return false;
			
		return $this->memcached->getStats();
	}
	
	function flush()
	{
		
		if(!$this->memcached)
			return false;
			
		return $this->memcached->flush();
	}
}