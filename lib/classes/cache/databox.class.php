<?php
class cache_databox
{
	private static $_instance = false;
	var $_c_obj = false;
	
	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	
	/**
	 * @return cache_databox
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	

	public function get($type,$what)
	{
			
		return $this->_c_obj->get(GV_ServerName.'_databox_'.$type.'_'.$what);
	}
	
	public function set($type,$what,$bin)
	{
		
		return $this->_c_obj->set(GV_ServerName.'_databox_'.$type.'_'.$what,$bin);
	}
	
	public function delete($type,$what)
	{
		
		return $this->_c_obj->delete(GV_ServerName.'_databox_'.$type.'_'.$what);
	}
	
	function refresh($sbas_id)
	{
		$date = new DateTime('-30 seconds');
		
		$cache_appbox = cache_appbox::getInstance();
		$last_update = $cache_appbox->get('memcached_update');
		if($last_update)
			$last_update = new DateTime($last_update);
		else
			$last_update = new DateTime('-10 years');

		if($date > $last_update && $cache_appbox->is_ok())
		{
			$connsbas = connection::getInstance($sbas_id);
			if($connsbas)
			{
				$sql = 'SELECT type, value FROM memcached WHERE site_id="'.$connsbas->escape_string(GV_ServerName).'"';
				if($rs = $connsbas->query($sql))
				{
					$cache_record = cache_record::getInstance();
					$cache_thumbnail = cache_thumbnail::getInstance();
					$cache_preview = cache_preview::getInstance();
					while($row = $connsbas->fetch_assoc($rs))
					{
						switch($row['type'])
						{
							case 'record':
								$cache_record->delete($sbas_id,$row['value'],false);
								$cache_thumbnail->delete($sbas_id,$row['value'],false);
								$cache_preview->delete($sbas_id,$row['value'],false);
								$sql = 'DELETE FROM memcached WHERE site_id="'.$connsbas->escape_string(GV_ServerName).'" AND type="record" AND value="'.$row['value'].'"';
								$connsbas->query($sql);
								break;
							case 'structure':
								$cache_appbox->delete('list_bases');
								$sql = 'DELETE FROM memcached WHERE site_id="'.$connsbas->escape_string(GV_ServerName).'" AND type="structure" AND value="'.$row['value'].'"';
								$connsbas->query($sql);
								break;
						}
					}
				}
			}
			
			$date = new DateTime();
			$now = phraseadate::format_mysql($date);
			$cache_appbox->set('memcached_update',$now);
			
			$conn = connection::getInstance();
			$sql = 'UPDATE sitepreff SET memcached_update="'.$conn->escape_string($now).'"';
			$conn->query($sql);
		}
	} 
	
	function update($sbas_id, $type, $value='')
	{
	
		$connbas = connection::getInstance($sbas_id);
		
		if($connbas)
		{
			$sql = 'SELECT distinct site_id as site_id FROM clients WHERE site_id != "'.$connbas->escape_string(GV_ServerName).'"';
			if($rs = $connbas->query($sql))
			{
				while($row = $connbas->fetch_assoc($rs))
				{
					$sql = 'REPLACE INTO memcached (site_id, type, value) VALUES ("'.$connbas->escape_string($row['site_id']).'","'.$connbas->escape_string($type).'","'.$connbas->escape_string($value).'")';
					$connbas->query($sql);
				}
				$connbas->free_result($rs);
			}
		}
		
		return true;
	}
	
}