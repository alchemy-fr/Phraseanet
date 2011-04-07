<?php
class order
{
	protected $storage = array();
	
	public function __construct($id)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$sql = 'SELECT o.id, o.usr_id, o.created_on, o.`usage`, o.deadline, COUNT(e.id) as total, o.ssel_id 
				FROM `order` o, order_elements e
				WHERE o.id = e.order_id
				AND o.id="'.$conn->escape_string($id).'"';

		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				try{
					$user = user::getInstance($row['usr_id']);
				}
				catch(Exception $e)
				{
					throw new Exception ($e);
				}

				$this->id			= $row['id'];
				$this->user 		= $user;
				$this->created_on 	= new DateTime($row['created_on']);
				$this->usage 		= $row['usage'];
				$this->deadline 	= new DateTime($row['deadline']);
				$this->total 		= (int)$row['total'];
				$this->ssel_id 		= (int)$row['ssel_id'];
			}
			else
				throw new Exception ('unknown order '.$id);
			$conn->free_result($rs);
		}
		
		
		$sql = 'SELECT e.base_id, e.record_id, e.order_master_id, e.id, e.deny 
				FROM order_elements e, order_masters m 
				WHERE order_id="'.$conn->escape_string($id).'" 
				AND m.usr_id = "'.$conn->escape_string($session->usr_id).'"
				AND m.base_id = e.base_id';
		
		$elements = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$display_name = '';
				$order_master_id = $row['order_master_id'] ? $row['order_master_id'] : false;
				if($order_master_id)
				{
					$user = user::getInstance($order_master_id);
					$display_name = $user->display_name;
				}
				
				$elements[$row['id']] = array(
					'base_id'			=> $row['base_id'],
					'record_id'			=> $row['record_id'],
					'deny'				=> !!$row['deny'],
					'order_master_id'	=> $order_master_id,
					'order_master_name'	=> $display_name,
					'thumbnail'			=> answer::getThumbnail($session->ses_id, $row['base_id'], $row['record_id'])
				);
			}
			$conn->free_result($rs);
		}
		
		$this->elements = $elements;
		return $this;
	}
	
	public function send_elements($elements_ids, $force)
	{
		$session = session::getInstance();	
		$conn = connection::getInstance();	
		
		$basrecs = array();
		foreach($elements_ids as $id)
		{
			if(isset($this->elements[$id]))
			{
				$basrecs[$id] = array('base_id'=>$this->elements[$id]['base_id'], 'record_id' => $this->elements[$id]['record_id']);
			}
		}
		
		try
		{
			$basket = basket::getInstance($this->ssel_id, $this->user->id);
		}
		catch(Exception $e)
		{
			$basket = new basket();
			
			$basket->name = sprintf(_('Commande du %s'), $this->created_on->format('Y-m-d'));
			$basket->pusher = $session->usr_id;
			$basket->usr_id = $this->user->id;
			$basket->save();
			
			$this->ssel_id = $basket->ssel_id;
			
			$sql = 'UPDATE `order` SET ssel_id="'.$conn->escape_string($basket->ssel_id).'" 
					WHERE id="'.$conn->escape_string($this->id).'"';
			$conn->query($sql);
		}
		
		$n = 0;
		foreach($basrecs as $order_element_id => $basrec)
		{
			try
			{
				$ret = $basket->push_element($basrec['base_id'], $basrec['record_id'], false, false);
				if($ret['error'] === false)
				{
					$sql = 'UPDATE order_elements SET deny="0", order_master_id="'.$conn->escape_string($session->usr_id).'" 
							WHERE order_id="'.$conn->escape_string($this->id).'" 
							AND id="'.$conn->escape_string($order_element_id).'"';
					
					if($force == '0')
					{
						$sql .= ' AND ISNULL(order_master_id)';
					}
					
					if($conn->query($sql))
						$n++;
					
				}
			}
			catch(Exception $e)
			{
				
			}
		}
		
		if($n > 0)
		{
			
			$evt_mngr = eventsmanager::getInstance();
			
			$params = array(
				'ssel_id'	=> $this->ssel_id,
				'from'		=> $session->usr_id,
				'to'		=> $this->user->id,
				'n'			=> $n
			);
			
			$evt_mngr->trigger('__ORDER_DELIVER__', $params);
		}
		
		$sql = 'UPDATE sselcont SET canHD="1" WHERE ssel_id="'.$this->ssel_id.'"';
		$conn->query($sql);
		
		return $this;
	}
		
	public function deny_elements($elements_ids)
	{
		$session = session::getInstance();
		$conn = connection::getInstance();
		
		foreach($elements_ids as $order_element_id)
		{
			$sql = 'UPDATE order_elements SET deny="1", order_master_id="'.$conn->escape_string($session->usr_id).'" 
					WHERE order_id="'.$conn->escape_string($this->id).'" 
					AND id="'.$conn->escape_string($order_element_id).'" AND ISNULL(order_master_id)';
			$conn->query($sql);
		}
		
		return $this;
	}
	
	public function __get($key)
	{
		if(isset($this->storage[$key]))
			return $this->storage[$key];
		return null;
	}
	
	public function __set($key, $value)
	{
		$this->storage[$key] = $value;
		return $this;
	}
	
	public function __isset($key)
	{
		if(isset($this->storage[$key]))
			return true;
		return false;
	}
}