<?php
class exportorder extends export
{
	
	public function order_avalaible_elements($from_usr_id, $usage, $deadline)
	{
  		$lst = $this->get_orderable_lst();
  		
  		$conn = connection::getInstance();
  		
  		$date = phraseadate::format_mysql(new DateTime($deadline));
  		
  		$conn->start_transaction();
  		
  		$usage = p4string::cleanTags($usage);
  		
  		$commit = true;
  		
  		$sql = 'INSERT INTO `order` (`id`, `usr_id`, `created_on`, `usage`, `deadline`) 
  				VALUES (null, "'.$conn->escape_string($from_usr_id).'", NOW(), 
  					"'.$conn->escape_string($usage).'", "'.$conn->escape_string($deadline).'")';
  		
  		if(!$conn->query($sql))
  		{
  			$commit = false;
  		}
  		else
  		{
  			$order_id = $conn->insert_id();
  		
	  		foreach($lst as $basrec)
	  		{
	  			$basrec = explode('_',$basrec);
	  			
	  			$base_id = $basrec[0];
	  			$record_id = $basrec[1];
	  			
	  			$sql = 'INSERT INTO order_elements (id, order_id, base_id, record_id, order_master_id) 
	  					VALUES (null, "'.$conn->escape_string($order_id).'", "'.$conn->escape_string($base_id).'", "'.$conn->escape_string($record_id).'", null)';
	  			
	  			if(!$conn->query($sql))
	  				$commit = false;
	  		}
  		}
  		
  		if($commit)
  		{
  			$conn->commit();
  		}
  		else
  		{
  			$conn->rollback();
  			return false;
  		}	
  		
  		
		$evt_mngr = eventsmanager::getInstance();
		
		$params = array(
			'order_id' => $order_id,
			'usr_id' => $from_usr_id
		);

		$evt_mngr->trigger('__NEW_ORDER__', $params);
		
		return true;
	}
	
	private function get_orderable_lst()
	{
		$ret = array();
		foreach($this->lst as $basrec=>$download_element)
  		{
			foreach($download_element->orderable as $name=>$bool)
			{
				if($bool === true)
				{
					$ret[] = $basrec;
				}
			}
  		}
  		return $ret;
	}
	
	public static function get_simple_users_list($base_id)
	{
		$conn = connection::getInstance();
		
		$sql = "SELECT u.usr_id, u.usr_login, o.id 
				FROM (usr u, basusr b)
					LEFT JOIN order_masters o ON (o.base_id = b.base_id AND u.usr_id=o.usr_id)   
				WHERE u.usr_login NOT LIKE '(#%'
				AND b.usr_id = u.usr_id
				AND ISNULL(o.id)
				AND b.actif='1'
				AND b.base_id = '".$conn->escape_string($base_id)."' 
				AND u.invite='0' 
				AND u.usr_login != 'autoregister' 
				AND u.usr_login != 'invite' 
				ORDER by usr_login ASC";
		$users = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
				$users[$row['usr_id']] = $row['usr_login'];
				
			$conn->free_result($rs);
		}
		echo $conn->last_error();
		
		return $users ;
				
	}
	

	
	public static function get_order_admins($base_id)
	{
		$conn = connection::getInstance();
		
		$sql = 'SELECT u.usr_id, u.usr_login 
				FROM usr u, order_masters o, basusr b
				WHERE o.usr_id = u.usr_id 
				AND o.base_id="'.$conn->escape_string($base_id).'"
				AND b.base_id = o.base_id
				AND b.usr_id = u.usr_id
				AND b.actif="1"';

		$users = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
				$users[$row['usr_id']] = $row['usr_login'];
				
			$conn->free_result($rs);
		}
		
		return $users ;
	}
	
	

	public static function set_order_admins($admins, $base_id)
	{
		$conn = connection::getInstance();
		
		$conn->start_transaction();
		$commit = true;
		
		$sql = 'DELETE FROM order_masters WHERE base_id="'.$conn->escape_string($base_id).'"';
		
		if(!$conn->query($sql))
			$commit = false;

    $cache_user = cache_user::getInstance();

		foreach($admins as $admin)
		{
			$sql = 'INSERT INTO order_masters (id, usr_id, base_id) VALUES (null, "'.$conn->escape_string($admin).'", "'.$conn->escape_string($base_id).'")';

			if(!$conn->query($sql))
				$commit = false;
      else
        $cache_user->delete ($admin);

		}
		
		if($commit)
			$conn->commit();
		else
			$conn->rollback();
		
		return;
	}
}