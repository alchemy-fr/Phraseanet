<?php
class eventsmanager
{
	
	private static $_instance = false;
	
	protected $events = array();
	
	protected $notifications = array();
	
	protected $pool_classes = array();
	
	private function __construct()
	{
		return $this;
	}
	
	function start()
	{
		$iterators_pool = array();
		$iterators_pool['event'][] = new DirectoryIterator(GV_RootPath.'lib/classes/event/');
		$iterators_pool['notify'][] = new DirectoryIterator(GV_RootPath.'lib/classes/notify/');
		
		if(file_exists(GV_RootPath.'config/classes/event/'))
			$iterators_pool['event'][] = new DirectoryIterator(GV_RootPath.'config/classes/event/');
		
		foreach($iterators_pool as $type=>$iterators)
		{
			foreach($iterators as $iterator)
			{
				foreach ($iterator as $fileinfo)
				{
					if (!$fileinfo->isDot())
					{
						if(substr($fileinfo->getFilename(),0,1) == '.')
							continue;
							
						$filename =  explode('.',$fileinfo->getFilename());
						$classname = $type.'_'.$filename[0];

						if(!class_exists($classname))
							continue;
						$this->pool_classes[$classname] = new $classname();//::getInstance();
						
						foreach($this->pool_classes[$classname]->get_events() as $event)
							$this->bind($event,$classname);
						
						if($type === 'notify' && $this->pool_classes[$classname]->is_avalaible())
							$this->notifications[] = $classname;
					}
				}
			}
		}
			
		return;
	}
	
	/**
	 * @return eventsmanager
	 */
	public static function getInstance()
	{
		if(!self::$_instance)
		{
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	
	function trigger($event,$array_params=array(),&$object = false)
	{
		if(array_key_exists($event, $this->events))
		{
			foreach($this->events[$event] as $classname)
			{
				$this->pool_classes[$classname]->fire($event,$array_params,$object);
			}
		}
		return;
	}
	
	function bind($event, $object_name)
	{
		
		if(!array_key_exists($event, $this->events))
			$this->events[$event] = array();
		
		$this->events[$event][] = $object_name;
	}
	
	function notify($usr_id, $event_type, $datas, $mailed=false)
	{
		$conn = connection::getInstance();
		
		$sql = 'INSERT INTO notifications (id, usr_id, type, unread, mailed, datas, created_on) VALUES 
			(null, "'.$conn->escape_string($usr_id).'","'.$conn->escape_string($event_type).'","'.$conn->escape_string(1).'"
			,"'.$conn->escape_string($mailed ? 1:0).'","'.$conn->escape_string($datas).'",NOW())';

		return $conn->query($sql) ? true : false;
	}

	function get_json_notifications($page=0)
	{
		
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$unread = 0;
		$total = 0;
		
		$sql = 'SELECT count(id) as total, sum(unread) as unread FROM notifications WHERE usr_id="'.$session->usr_id.'"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$unread	= $row['unread'];
				$total 	= $row['total'];
			}
			$conn->free_result($rs);
		}
		
		$n = 10;
		
		$sql = 'SELECT * FROM notifications WHERE usr_id="'.$session->usr_id.'" ORDER BY created_on DESC LIMIT '.((int)$page * $n).', '.$n;
		
		$datas = array('notifications'=>array(),'next'=>'');
		
		
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$data = $this->pool_classes[$row['type']]->datas($row['datas'],$row['unread']);
				
				if(!isset($this->pool_classes[$row['type']]))
				{
					$sql = 'DELETE FROM notifications WHERE id="'.$row['id'].'"';
					$conn->query($sql);
					continue;
				}	
				
				$date_key = str_replace('-','_',substr($row['created_on'],0,10));
				$display_date = phraseadate::getDate(new DateTime($row['created_on']));
				
				if(!isset($datas['notifications'][$date_key]))
				{
					$datas['notifications'][$date_key] = array(
						'display'		=>	$display_date
						,'notifications'	=> array()
					);
				}
				
				$datas['notifications'][$date_key]['notifications'][$row['id']] = array(
					'classname' => $data['class']
					,'time'	=> phraseadate::getTime(new DateTime($row['created_on']))
					,'icon'	=> '<img src="'.$this->pool_classes[$row['type']]->icon_url().'" style="vertical-align:middle;width:16px;margin:2px;" />'
					,'id'	=> $row['id']
					,'text'	=> $data['text']
				);
				
//				$html = '<div style="position:relative;" class="'.$data['class'].'">'.
//						$data['text'].' <span class="time">'.phraseadate::getPrettyString(new DateTime($row['created_on'])).'</span></div>';
//
//				$bloc[] = '<div style="position:relative;" id="notification_'.$row['id'].'" class="notification '.($row['unread'] == '1' ? 'unread':'').'">'.
//					'<table style="width:100%;" cellspacing="0" cellpadding="0" border="0"><tr><td style="width:25px;">'.
//					'<img src="'.$this->pool_classes[$row['type']]->icon_url().'" style="vertical-align:middle;width:16px;margin:2px;" />'.
//					'</td><td>'.
//					$html.
//					'</td></tr></table>'.
//					'</div>';
			}
			$conn->free_result($rs);
		}
		
		if(((int)$page + 1) * $n < $total)
		{
			$datas['next'] = '<a href="#" onclick="print_notifications('.((int)$page + 1).');return false;">'._('charger d\'avantages de notifications').'</a>';
		}
		
		return p4string::jsonencode($datas);
	}
	
	
	function get_unread_notifications_number()
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$total = 0;
		
		$sql = 'SELECT count(id) as total FROM notifications WHERE usr_id="'.$session->usr_id.'" AND unread="1"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$total 	= $row['total'];
			}
			$conn->free_result($rs);
		}
		return $total;
	}
	
	
	function get_notifications()
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$unread = 0;
		$total = 0;
		
		$sql = 'SELECT count(id) as total, sum(unread) as unread FROM notifications WHERE usr_id="'.$session->usr_id.'"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$unread	= $row['unread'];
				$total 	= $row['total'];
			}
			$conn->free_result($rs);
		}
		
	
		if($unread < 3)
		{
			$sql = 'SELECT * FROM notifications WHERE usr_id="'.$session->usr_id.'" ORDER BY created_on DESC LIMIT 0,4';
		}
		else
		{
			$sql = 'SELECT * FROM notifications WHERE usr_id="'.$session->usr_id.'" AND unread="1" ORDER BY created_on DESC';
		}
	
		$bloc = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$datas = $this->pool_classes[$row['type']]->datas($row['datas'],$row['unread']);
				
				if(!isset($this->pool_classes[$row['type']]))
				{
					$sql = 'DELETE FROM notifications WHERE id="'.$row['id'].'"';
					$conn->query($sql);
					continue;
				}
				
				$html = '<p style="margin:0;padding:0;" class="'.$datas['class'].'">'.
						$datas['text'].' <span class="time">'.phraseadate::getPrettyString(new DateTime($row['created_on'])).'</span></p>';

				$bloc[] = '<div style="position:relative;" id="notification_'.$row['id'].'" class="notification '.($row['unread'] == '1' ? 'unread':'').'">'.
					'<table style="width:100%;" cellspacing="0" cellpadding="0" border="0"><tr><td style="width:25px;">'.
					'<img src="'.$this->pool_classes[$row['type']]->icon_url().'" style="vertical-align:middle;width:16px;margin:2px;" />'.
					'</td><td>'.
					$html.
					'</td></tr></table>'.
					'</div>';
			}
			$conn->free_result($rs);
		}
		
		$html = '';
		
		if(count($bloc) == 0)
			$html .= '<div class="notification_title">
							<span>'._('Aucune notification').'</span>
						</div>';
		else
		{
			$html .= '<div class="notification_title">
							<a href="#" onclick="print_notifications(0);return false;">'._('toutes les notifications').'</a>
						</div>';
				
			$html .= implode('',$bloc);
			
		}
		
			
		return '<div style="margin-right:16px;">'.$html.'</div>';
	}
	
	function read(Array $notifications, $usr_id)
	{
		$conn = connection::getInstance();
		
		if(count($notifications) == 0)
			return false;
		
		$sql = 'UPDATE notifications SET unread="0" WHERE usr_id="'.$conn->escape_string($usr_id).'" AND (id="'.implode('" OR id="',$notifications).'")';
		
		$conn->query($sql);
		
		return ;
	}
	
	function mailed($notification, $usr_id)
	{
		$conn = connection::getInstance();
		
		$sql = 'UPDATE notifications SET mailed="0" WHERE usr_id="'.$conn->escape_string($usr_id).'" AND id="'.$notifications.'"';
		$conn->query($sql);
		
		return ;
	}
	
	function list_notifications_avalaible($usr_id)
	{
		
		$personnal_notifications = array();
		
		foreach($this->notifications as $notification)
		{
			$group = $this->pool_classes[$notification]->get_group();
			$group = $group === null ? _('Notifications globales') : $group;
			
			$personnal_notifications[$group][] = array(
				'name' 			=> $this->pool_classes[$notification]->get_name()
				,'id' 			=> $notification
				,'description'	=> $this->pool_classes[$notification]->get_description()
				,'subscribe_emails'=> true
			);
		}
		
		return $personnal_notifications;
	}
	
}