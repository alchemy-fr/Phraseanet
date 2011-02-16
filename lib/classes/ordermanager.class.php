<?php
class ordermanager
{
	protected $storage = array();
	
	public function __construct($sort = false, $page = 1)
	{
		$session = session::getInstance();
		$conn = connection::getInstance();
		
		$page = (int)$page ;
		
		$quantite = 10;
		$debut = ($page-1) * $quantite;
		
		$sql = 'SELECT distinct o.id, o.usr_id, created_on, deadline, `usage`, COUNT(e2.id) as todo 
				FROM (`order_elements` e, `order` o, `order_masters` m) 
				LEFT JOIN order_elements e2 ON (ISNULL(e2.order_master_id) AND m.base_id = e2.base_id AND e.id = e2.id) 
				WHERE m.usr_id = "'.$conn->escape_string($session->usr_id).'" 
				AND m.base_id = e.base_id AND e.order_id = o.id 
				GROUP BY o.id  
				ORDER BY o.id DESC
				LIMIT '.(int)$debut.','.$quantite;
		
		$orders = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$user = user::getInstance($row['usr_id']);
				$row['created_on'] = new DateTime($row['created_on']);
				$row['deadline'] = new DateTime($row['deadline']);
				$orders[] = array_merge(array('usr_display'=>$user->display_name),$row);
			}
			$conn->free_result($rs);
		}
		echo $conn->last_error();
		if($sort)
		{
			if($sort == 'created_on')
				uasort($orders,array('ordermanager','date_orders_sort'));
			elseif($sort == 'user')
				uasort($orders,array('ordermanager','user_orders_sort'));
			elseif($sort == 'usage')
				uasort($orders,array('ordermanager','usage_orders_sort'));
		}
		
		
		$sql = 'SELECT distinct o.id
				FROM (`order_elements` e, `order` o, `order_masters` m) 
				WHERE m.usr_id = "'.$conn->escape_string($session->usr_id).'" 
				AND m.base_id = e.base_id AND e.order_id = o.id 
				GROUP BY o.id
				ORDER BY o.id DESC';
		
		if($rs = $conn->query($sql))
		{
			$total = $conn->num_rows($rs);
			$conn->free_result($rs);
		}
		
		$p_page = $page < 2 ? false : ($page - 1);
		$t_page = ceil($total / $quantite);
		$n_page = $page >= $t_page ? false : $page + 1;
		
		$this->orders = $orders;
		$this->page = $page;
		$this->previous_page = $p_page;
		$this->next_page = $n_page;
		$this->total = $total;
		
		return $this;
	}
	
	private static function usage_orders_sort($a, $b)
	{
		$comp = strcasecmp($a['usage'], $b['usage']);
		
		if($comp == 0)
			return 0;
			
		return $comp < 0 ? -1 : 1;
	}
	
	private static function user_orders_sort($a, $b)
	{
		$comp = strcasecmp($a['usr_display'], $b['usr_display']);
		
		if($comp == 0)
			return 0;
			
		return $comp < 0 ? -1 : 1;
	}
	
	private static function date_orders_sort($a, $b)
	{
		$comp = $b->format('U') - $a->format('U');
		
		if($comp == 0)
			return 0;
			
		return $comp < 0 ? -1 : 1;
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