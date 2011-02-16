<?php




class basketCollection
{
	private $baskets = array();


	function __get($name)
	{
		if ($this->$name) {
            return $this->$name;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
	}
	public function __isset($name)
	{
		if (isset($this->$name))
		{
			return true;
		}

		return false;
	}
	/**
	 * @param string $order (optionnal name_asc or date_desc - defaut to name_asc)
	 * @param array $except (array of element not return. avalaible values are regroup baskets and recept)
	 * @return basketCollectionObject
	 */
	function __construct($order='name asc',$except = array())
	{

		$session = session::getInstance();
		$ses = $session->ses_id;
		$usr = $session->usr_id;
		if(!($ph_session = phrasea_open_session($ses,$usr)))
			return;

		$current_timestamp_obj = new DateTime();
		$current_timestamp = $current_timestamp_obj->format('U');

		$baskets = false;

		if(!$baskets)
		{

			$conn = connection::getInstance();

			$sql = 'SELECT ssel_id FROM ssel WHERE usr_id="'.$conn->escape_string($usr).'" AND temporaryType="0" and deleted="0"';
			if($rs = $conn->query($sql))
			{
				if($conn->num_rows($rs) == 0)
				{
					$basket = new basket();
					$basket->save();
				}
				$conn->free_result($rs);
			}

			$baskets = array();
			$baskets['baskets'] = $baskets['recept'] = $baskets['regroup'] = array();

			$sql = 'SELECT s.ssel_id, s.usr_id as owner, v.id as validate_id, s.temporaryType, s.pushFrom, v.expires_on FROM ssel s
					LEFT JOIN validate v ON (v.ssel_id = s.ssel_id AND v.usr_id="'.$conn->escape_string($usr).'")
					WHERE (s.usr_id="'.$conn->escape_string($usr).'" OR v.id IS NOT NULL) and deleted="0"';

			if($rs = $conn->query($sql))
			{

				while($row = $conn->fetch_assoc($rs))
				{
					try {
						$is_mine = ($row['owner'] == $session->usr_id);

						$expires_on_obj = new DateTime($row['expires_on']);
						$expires_on = $expires_on_obj->format('U');

						if($row['validate_id'] != null && !$is_mine && $expires_on < $current_timestamp)
							continue;

						if($row['temporaryType'] == '1')
							$baskets['regroup'][] = basket::getInstance($row['ssel_id']);
						elseif(!is_null($row['validate_id']))
							$baskets['baskets'][] = basket::getInstance($row['ssel_id']);
						elseif((int)$row['pushFrom'] > 0)
							$baskets['recept'][] = basket::getInstance($row['ssel_id']);
						else
							$baskets['baskets'][] = basket::getInstance($row['ssel_id']);
					}
					catch(Exception $e)
					{

					}
				}

				$conn->free_result($rs);
			}
		}

		$to_remove = array_intersect(array('recept','regroup','baskets'),$except);

		foreach($to_remove as $type)
			$baskets[$type] = array();

		if($order == 'name asc')
		{
			uasort($baskets['baskets'],array('basketCollection','story_name_sort'));
			uasort($baskets['regroup'],array('basketCollection','story_name_sort'));
			uasort($baskets['recept'],array('basketCollection','story_name_sort'));
		}
		if($order == 'date desc')
		{
			uasort($baskets['baskets'],array('basketCollection','story_date_sort'));
			uasort($baskets['regroup'],array('basketCollection','story_date_sort'));
			uasort($baskets['recept'],array('basketCollection','story_date_sort'));
		}

		$this->baskets = $baskets;

		return $this;
	}


	function get_names()
	{
		$array_names = array();

		foreach($this->baskets as $type_name=>$type)
		{
			foreach($type as $basket)
			{

				$array_names[] = array('ssel_id'=>$basket->ssel_id,'name'=>$basket->name,'type'=>$type_name);
			}
		}

		return $array_names;
	}




	function story_date_sort($a, $b)
	{
		if(!$a->create || !$b->create)
			return 0;

		$comp = strcasecmp($a->create, $b->create);

		if($comp == 0)
			return 0;

		return $comp < 0 ? -1 : 1;
	}
	function story_name_sort($a, $b)
	{
		if(!$a->name || !$b->name)
		{
			return 0;
		}
		$comp = strcasecmp($a->name, $b->name);

		if($comp == 0)
			return 0;

		return $comp < 0 ? -1 : 1;
	}

}