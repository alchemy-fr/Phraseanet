<?php

class cache_basket
{

	private static $_instance = false;
	var $_c_obj = false;
	
	function __construct()
	{
		$this->_c_obj = cache::getInstance();
	}
	
	/**
	 * @return cache_basket
	 */
	public static function getInstance()
	{
		
		if (!(self::$_instance instanceof self))
            self::$_instance = new self();
 
        return self::$_instance;
		
	}
	
	public function get($usr_id, $ssel_id)
	{
			
		return $this->_c_obj->get(GV_ServerName.'_basket_'.$usr_id.'_'.$ssel_id);
	}
	
	public function set($usr_id, $ssel_id,$value)
	{
		
		return $this->_c_obj->set(GV_ServerName.'_basket_'.$usr_id.'_'.$ssel_id,$value);
	}
	
	public function delete($usr_id, $ssel_id)
	{
		try
		{
			$basket = basket::getInstance($ssel_id, $usr_id);
			
			if($basket->valid)
			{
				foreach($basket->validating_users as $user_data)
				{
					$this->_c_obj->delete(GV_ServerName.'_basket_'.$user_data['usr_id'].'_'.$ssel_id);
				}
			}
	
			return $this->_c_obj->delete(GV_ServerName.'_basket_'.$usr_id.'_'.$ssel_id);
		}
		catch(Exception $e)
		{
			
		}
	}
	/**
	 * Revoke cache when user rights have been modified - do not cache datas which are now forbidden
	 * @param $usr_id
	 * @return boolean
	 */
	public function revoke_baskets_usr($usr_id)
	{
		$conn = connection::getInstance();
		$ssel_ids = array();
		if($conn)
		{
			$sql = 'SELECT ssel_id FROM ssel WHERE deleted="0"';
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$ssel_ids[] = GV_ServerName.'_basket_'.$usr_id.'_'.$row['ssel_id'];
				}
				$conn->free_result($rs);
			}
		}
		
		return $this->_c_obj->deleteMulti($ssel_ids);
	}
	
	
	/**
	 * Revoke cache when user documents have their collection changed or status - do not cache datas which are now forbidden
	 * @param $usr_id
	 * @return boolean
	 */
	public function revoke_baskets_record($records_array)
	{
		$conn = connection::getInstance();
		$keys = array();
		if($conn)
		{
			$sql = 'SELECT s.ssel_id, s.usr_id FROM ssel s, sselcont c WHERE (c.record_id="'.implode('" OR c.record_id="',$records_array).'") AND c.ssel_id = s.ssel_id';
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$keys[] = GV_ServerName.'_basket_'.$row['usr_id'].'_'.$row['ssel_id'];
				}
				$conn->free_result($rs);
			}
		}
		
		return $this->_c_obj->deleteMulti($keys);
	}
	
	
	
}