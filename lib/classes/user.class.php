<?php

if(!defined('GV_defaultQuery'))
	define('GV_defaultQuery','last');
if(!defined('GV_bandeauHome'))
	define('GV_bandeauHome','QUERY');
	

class user
{
	
	var $id = false;
	
	var $_rights_sbas = array(
	);
	var $_rights_bas = array(
	);
	var $_rights_records = array(
	);
	var $_global_rights = array(
		'taskmanager'=>false,
		'manageusers'=>false,
		'order'=>false,
		'report'=>false,
		'push'=>false,
		'addrecord'=>false,
		'modifyrecord'=>false,
		'changestatus'=>false,
		'doctools'=>false,
		'deleterecord'=>false,
		'addtoalbum'=>false,
		'coll_modify_struct'=>false,
		'coll_manage'=>false,
		'order_master'=>false,
		'bas_modif_th'=>false,
		'bas_modify_struct'=>false,
		'bas_manage'=>false,
		'bas_chupub'=>false
	);
	
	public static $locales = array(
			'ar_SA'	=> 'العربية'
			,'de_DE' => 'Deutsch'
			,'en_GB' => 'English'
//			,'es_LA'	=> 'Español'
			,'fr_FR' => 'Fran&ccedil;ais'
//			,'nb_NO'	=> 'Norsk (bokmål)'
//			,'zh_CN'	=> '中文(简体)'
		);
	
	private $data = array(
		'password'=>'',
		'email'=>'',
		'login'=>'',
		'is_admin'=>false,
		'firstname' => false,
		'display_name'=>false,
		'gender' => false,
		'lastname' => false,
		'address' => false,
		'city' => false,
		'geonameid' => false,
		'zip' => false,
		'tel' => false,
		'fax' => false,
		'job' => false,
		'company'=>false
	);
	
	private static $_instance = array();
	
	var $_prefs = array();
	
	private static $_users = array();
	
	var $_updated_prefs = array();
	
	private static $def_values = array(
			'view' 					=> 'thumbs',
			'images_per_page' 		=> 20,
			'images_size' 			=> 120,
			'editing_images_size' 	=> 134,
			'editing_top_box' 		=> '180px',
			'editing_right_box' 	=> '400px',
			'editing_left_box' 		=> '710px',
			'basket_sort_field'		=> 'name',
			'basket_sort_order'		=> 'ASC',
			'warning_on_delete_story'=>'true',
			'client_basket_status'	=> '1',
			'css'					=> '000000',
			'start_page_query'		=> GV_defaultQuery,
			'start_page'			=> GV_bandeauHome,
			'rollover_thumbnail'	=> 'caption',
			'technical_display'		=> '1',
			'doctype_display'		=> '1',
			'bask_val_order'		=> 'nat',
			'basket_caption_display'=> '0',
			'basket_status_display'	=> '0',
			'basket_title_display'	=> '0'
		);
	private static $avalaible_values = array(
			'view' 					=> array('thumbs','list'),
			'basket_sort_field'		=> array('name','date'),
			'basket_sort_order'		=> array('ASC','DESC'),
			'start_page'			=> array('PUBLI','QUERY','LAST_QUERY','HELP'),
			'technical_display'		=> array('0','1','group'),
			'rollover_thumbnail'	=> array('caption','preview'),
			'bask_val_order'		=> array('nat','asc', 'desc')
		);
	

	/**
	 * @return user
	 */
	public static function getInstance($id=false)
	{
		if(is_int((int)$id) && (int)$id > 0)
		{
			$id = (int)$id;
		}
		else
			throw new Exception ('Invalid usr_id');
			
		if(!isset(self::$_instance[$id]))
		{
			$cache_user = false;
			if(defined('GV_memcached') && GV_memcached)
			{
				$cache_user = cache_user::getInstance();
			}
			if($cache_user && (($tmp = $cache_user->get($id)) != false))
			{
				self::$_instance[$id] = $tmp;
			}
			else
			{
				self::$_instance[$id] = new user($id);
			
				if($cache_user)
					$cache_user->set($id, self::$_instance[$id]);
			}
		}
		return array_key_exists($id, self::$_instance) ? self::$_instance[$id] : false;
	}
	
	public static function clear_cache($id)
	{
		$cache = cache_user::getInstance($id);
		
		return $cache->delete($id);
	}
	
	function __construct($id=false)
	{
		if($id !== false)
		{
			return $this->load($id);
		}
		
		return true;
	}
	
	public static function getMyRss($renew = false)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();

		$token = $title = false;
		
		$sql = 'SELECT value FROM tokens WHERE usr_id="'.$conn->escape_string($session->usr_id).'" AND type="rss"';
		if(!$renew)
		{
			if(($rs = $conn->query($sql)))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$token = $row['value'];
				}
				$conn->free_result($rs);
			}
		}
		else
		{
			$sql = 'DELETE FROM tokens WHERE usr_id="'.$conn->escape_string($session->usr_id).'" AND type="rss"';
			$conn->query($sql);
		}
		if($token === false)
		{
			$token = random::getUrlToken('rss',$session->usr_id);
		}
		$texte = false;
		if($token !== false)
		{
			
			$texte = '<p>'._('publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.').'</p><p>'._('publications::Ne le partagez pas, il est strictement confidentiel').'</p>
				<div><input type="text" style="width:100%" value="'.GV_ServerName.'atom/'.$token.'"/></div>';
			$title = _('publications::votre rss personnel');
		}
		
		return array('texte'=>$texte,'titre'=>$title, 'token'=>$token, 'url'=>GV_ServerName.'atom/'.$token);
		
	}
	
	
	/**
	 * Query in the cache
	 * 
	 * @param unknown_type $query
	 * @return unknown_type
	 */
	public static function saveQuery($query)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$usr_id = $session->usr_id;
		$ses_id = $session->ses_id;
		
		if(($id = $conn->getId("DSEL")) != false)
		{				
			$sql = "INSERT INTO dsel (id, name, usr_id, query) VALUES ('".$conn->escape_string($id)."','".$conn->escape_string($query)."', '". $conn->escape_string($usr_id)."', '".$conn->escape_string($query)."')";
			$conn->query($sql);
		}
		
		if(user::getPrefs('start_page') == 'LAST_QUERY')
			user::setPrefs('start_page_query',$query);
		
		$sql = 'UPDATE cache SET query = "'.$conn->escape_string($query).'" WHERE usr_id = "'.$conn->escape_string($usr_id).'" AND session_id="'.$conn->escape_string($ses_id).'"';
		
		if($conn->query($sql))
		{
			return true;
		}	
		return false;
	}

	public static function getInfos($usr)
	{
    $display_name = _('phraseanet::utilisateur inconnu');
    try
    {
		$user = self::getInstance($usr);
      $display_name = $user->display_name;
    }
    catch(Exception $e)
    {
		
	}
    return $display_name;
  }

	public function __set($name,$value)
	{
		if($name === 'password')
			$value = hash('sha256',$value);
		$this->data[$name] = $value;
	}
	
	public function __get($name)
	{
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
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
    	if(array_key_exists($name, $this->data))
    		return true;
    	return false;
    }
	
    public function save()
    {
		$conn = connection::getInstance();
    	if($this->id === false)
		{
			if(!$conn)
				throw new Exception('Unable to get valid connection');	
			if(($id = $conn->getId('USR'))!==false)
			{
				if(trim($this->login) !== '' && trim($this->password) !== '')
				{
					$sql = 'INSERT INTO usr (usr_id, usr_login, usr_password, usr_creationdate) VALUES ("'.$conn->escape_string($id).'", "'.$conn->escape_string($this->login).'", "'.$conn->escape_string($this->password).'", NOW())';
					if($conn->query($sql))
						$this->id = $id;
					else
						throw new Exception('Error while saving user : '.$conn->last_error());	
				}
				else
					throw new Exception('Invalid username or password');	
			}
			else
				throw new Exception('Unable to get new usr_id');	
		}
		
    	if($this->id !== false)
		{
			$sql = 'UPDATE usr SET 
				usr_login 				= "'.$conn->escape_string($this->login).'"
				,usr_password 			= "'.$conn->escape_string($this->password).'"
				,usr_mail 				= '.(trim($this->email) != '' ? '"'.$conn->escape_string($this->email).'"' : 'null').'
				,usr_modificationdate 	= NOW()
				,create_db 					= "'.($this->is_admin?'1':'0').'"
				WHERE usr_id = "'.$this->id.'"';
			
			if(!$conn->query($sql))
			{
				throw new Exception('unable to update');	
			}
			$this->id = $id;
		}
		return $this->id;
    }
	
    public function load($id)
    {
    	
    	$conn = connection::getInstance();
		if($conn && $id !== false)
		{
			if((int)$id>0)
			{
				$sql = 'SELECT usr_id, create_db, usr_login, usr_nom, usr_prenom, usr_sexe as gender, 
					usr_mail, adresse, ville, cpostal, tel, fax, fonction, societe, geonameid
					FROM usr WHERE usr_id="'.$conn->escape_string((int)$id).'"';
			}
			elseif(is_string($id))
			{
				$sql = 'SELECT usr_id, create_db, usr_login, usr_nom, usr_prenom, usr_sexe as gender, 
					usr_mail, adresse, ville, cpostal, tel, fax, fonction, societe, geonameid 
					FROM usr WHERE usr_login="'.$conn->escape_string((string)$id).'"';
			}
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$this->id = $row['usr_id'];
					$this->email = $row['usr_mail'];
					$this->login = $row['usr_login'];
					
					$this->firstname 	= $row['usr_nom'];
					$this->lastname 	= $row['usr_prenom'];
					$this->address 		= $row['adresse'];
					$this->city 		= $row['ville'];
					$this->geonameid	= $row['geonameid'];
					$this->zip 			= $row['cpostal'];
					$this->gender 		= $row['gender'];
					$this->tel 			= $row['tel'];
					$this->fax 			= $row['fax'];
					$this->job 			= $row['fonction'];
					$this->company		= $row['societe'];
					
					
					if( trim($row['usr_nom']) !=='' || trim($row['usr_prenom']) !== '')
						$display_name = $row['usr_prenom'].' '.$row['usr_nom'];
					elseif(trim($row['usr_mail']) !== '')
						$display_name = $row['usr_mail'];
					else
						$display_name = $row['usr_login'];
					
					$this->display_name = $display_name;
					
					$this->is_admin = $row['create_db'] == '1' ? true : false;
					$this->_global_rights['taskmanager'] =  $this->is_admin;
				}
				$conn->free_result($rs);
			}
			if(!$this->id)
			{
				throw new Exception(_('Undefined usr_id '.$id));
		        trigger_error('Undefined usr_id '.$id.' in ' . $trace[0]['file'] .' on line ' . $trace[0]['line'],  E_USER_NOTICE);
				return false;
			}
			$sql = 'select DISTINCT c.base_id,c.record_id 
					FROM (sselcont c, ssel s) 
					LEFT JOIN (validate u) ON (u.usr_id = "'.$conn->escape_string($this->id).'" and u.can_hd=1 AND u.ssel_id = c.ssel_id) 
					WHERE c.ssel_id =s.ssel_id AND s.usr_id="'.$conn->escape_string($this->id).'" AND (c.canHD = 1 OR u.ssel_id = s.ssel_id)';
					
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$currentid = $row["base_id"]."_".$row["record_id"];
					$this->_rights_records[$currentid] = $currentid;
				}
				$conn->free_result($rs);
			}
				
			$sql = 'SELECT base_id, canaddrecord, manage, canadmin, chgstatus, candwnldpreview, canpreview, candwnldhd, needwatermark, restrict_dwnld, 
					remain_dwnld, canmodifrecord, canputinalbum, canreport, mask_and, mask_xor, candeleterecord, imgtools, canpush, cancmd, modify_struct 
					FROM basusr WHERE usr_id="'.$conn->escape_string($this->id).'" AND actif="1"';
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					if($row['canadmin'] == '1')
						$this->_global_rights['manageusers'] = true;
					if($row['manage'] == '1')
						$this->_global_rights['coll_manage'] = true;
					if($row['modify_struct'] == '1')
						$this->_global_rights['coll_modify_struct'] = true;
					if($row['cancmd'] == '1')
						$this->_global_rights['order'] = true;
					if($row['canpush'] == '1')
						$this->_global_rights['push'] = true;
					if($row['canaddrecord'] == '1')
						$this->_global_rights['addrecord'] = true;
					if($row['canmodifrecord'] == '1')
						$this->_global_rights['modifyrecord'] = true;
					if($row['chgstatus'] == '1')
						$this->_global_rights['changestatus'] = true;
					if($row['imgtools'] == '1')
						$this->_global_rights['doctools'] = true;
					if($row['candeleterecord'] == '1')
						$this->_global_rights['deleterecord'] = true;
					if($row['canputinalbum'] == '1')
						$this->_global_rights['addtoalbum'] = true;
					if($row['canreport'] == '1')
						$this->_global_rights['report'] = true;

					
					$this->_rights_bas[$row['base_id']]['chgstatus'] 		= ($row['chgstatus'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['cancmd']		 	= ($row['cancmd'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['canaddrecord'] 	= ($row['canaddrecord'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['canpush'] 			= ($row['canpush'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['candeleterecord'] 	= ($row['candeleterecord'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['canadmin'] 		= ($row['canadmin'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['chgstatus'] 		= ($row['chgstatus'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['candwnldpreview'] 	= ($row['candwnldpreview'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['canpreview'] 		= ($row['canpreview'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['candwnldhd'] 		= ($row['candwnldhd'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['needwatermark'] 	= ($row['needwatermark'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['restrict_dwnld'] 	= ($row['restrict_dwnld'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['remain_dwnld'] 	= ($row['remain_dwnld'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['canmodifrecord'] 	= ($row['canmodifrecord'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['canputinalbum'] 	= ($row['canputinalbum'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['canreport'] 		= ($row['canreport'] == '1' ? true : false);
					$this->_rights_bas[$row['base_id']]['mask_and']		 	= $row['mask_and'];
					$this->_rights_bas[$row['base_id']]['mask_xor']		 	= $row['mask_xor'];
					$this->_rights_bas[$row['base_id']]['order_manager'] 	= false;
					
				}
				$conn->free_result($rs);
			}
				
			$sql = 'SELECT * FROM sbasusr WHERE usr_id="'.$conn->escape_string($this->id).'"';
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					
					if($row['bas_modif_th'] == '1')
						$this->_global_rights['bas_modif_th'] = true;
					if($row['bas_modify_struct'] == '1')
						$this->_global_rights['bas_modify_struct'] = true;
					if($row['bas_manage'] == '1')
						$this->_global_rights['bas_manage'] = true;
					if($row['bas_chupub'] == '1')
						$this->_global_rights['bas_chupub'] = true;
					
					$this->_rights_sbas[$row['sbas_id']]['bas_modify_struct'] = ($row['bas_modify_struct'] == '1' ? true : false);
					$this->_rights_sbas[$row['sbas_id']]['bas_manage'] = ($row['bas_manage'] == '1' ? true : false);
					$this->_rights_sbas[$row['sbas_id']]['bas_chupub'] = ($row['bas_chupub'] == '1' ? true : false);
					$this->_rights_sbas[$row['sbas_id']]['bas_modif_th'] = ($row['bas_modif_th'] == '1' ? true : false);
					
				}
				$conn->free_result($rs);
			}
			
			$sql = 'SELECT base_id FROM order_masters WHERE usr_id="'.$conn->escape_string($id).'"';
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$this->_global_rights['order_master'] = true;
					$this->_rights_bas[$row['base_id']]['order_manager'] = true;
				}
				$conn->free_result($rs);
			}
			
			
			$sql = 'SELECT prop, value FROM usr_settings WHERE usr_id="'.$conn->escape_string($id).'"';
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$this->_prefs[$row['prop']] = $row['value'];
				}
				
				$conn->free_result($rs);
			}
			
			$evt_mngr = eventsmanager::getInstance();
			$notifications = $evt_mngr->list_notifications_avalaible($this->id);
			
			foreach($notifications as $notification_group=>$nots)
			{
				foreach($nots as $notification)
				{
					if(!isset($this->_prefs['notification_'.$notification['id']]))
					{
						$this->_prefs['notification_'.$notification['id']] = '1';
	//					$this->_updated_prefs[] = 'notification_'.$notification['id'];
						$this->update_pref('notification_'.$notification['id'],'1');
					}
				}
			}
			
			foreach(self::$def_values as $k=>$v)
			{
				if(!isset($this->_prefs[$k]))
				{
					$this->_prefs[$k] = $v;
					$this->update_pref($k,$v);
				}
			}
			
			return true;
		}
		return false;
    }
    
	function update_pref($prop,$value)
	{
		if(!isset($this->id))
			return false;
		
		$conn = connection::getInstance();

		$sql = 'REPLACE INTO usr_settings (usr_id, prop, value) VALUES ("'.$conn->escape_string($this->id).'","'.$conn->escape_string($prop).'",		'.(is_null($conn->escape_string($value)) ? 'NULL' : '"'.$conn->escape_string($value).'"').')';
		$conn->query($sql);
		
		$cache = cache_user::getInstance();
		$cache->set($this->id, $this);
		
		return;
	}
	
	
	public static function avLanguages()
	{
		$lngs = array();
		
		$path = dirname(__FILE__). "/../../locale";
		if($hdir = opendir($path))
		{
			while(false !== ($file = readdir($hdir)))
			{
				if(substr($file,0,1)=="." || mb_strtolower($file)=="cvs")
					continue;
				if(is_dir($path . "/" . $file) && strpos($file,'_') == 2 && strlen($file) == 5)
				{
					if(!array_key_exists($file,self::$locales))
						continue;
					$supFile = explode('_',$file);
					if(!isset($lngs[$supFile[0]]))
						$lngs[$supFile[0]] = array();
					$lngs[$supFile[0]][$file] = array('name'=>self::$locales[$file],'selected'=>false);					
				}
			}
		}	
		return $lngs;
		
	}
	
	
		
	
	public static function detectLanguage($setLng = null)
	{
		$avLanguages = self::avLanguages();
		$sel = $askLng = $askLocale = '';
		
		$session = session::getInstance();
		$session->usr_i18n = $session->usr_l10n = false;
		
		$lng = GV_default_lng;
		
		if($setLng !== null)
		{
			$askLng = substr($setLng,0,2);
			$askLocale = $setLng;
		}
		elseif($session->isset_cookie('locale'))
		{
			$askLng = substr($session->get_cookie('locale'),0,2);
			$askLocale = $session->get_cookie('locale');
		}
		elseif(defined('GV_default_lng'))
		{
			$askLng = substr(GV_default_lng,0,2);
			$askLocale = GV_default_lng;
		}
		
		
		if($askLng != '' && isset($avLanguages[$askLng]) && isset($avLanguages[$askLng][$askLocale]))
		{
			$avLanguages[$askLng][$askLocale]['selected'] = true;
			$sel = $askLocale;
		}
		
		if($sel === '' && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$languages = explode(';',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$found= false;
			
			foreach($languages as $language)
			{
				$language = explode(',',mb_strtolower($language));
				if(count($language) != 2)
					continue;
					
				foreach($language as $lang)
				{
					if(strpos($lang,'-') == 2 && strlen($lang) == 5)
					{
						$l = explode('-',$lang);
						$l[0] = mb_strtolower($l[0]);
						$l[1] = strtoupper($l[1]);
						
						if($sel != '')
						{
							$found = true;
							break;
						}
						$lang = implode('_',$l);
						if(isset($avLanguages[$l[0]]))
						{
							if(!isset($avLanguages[$l[0]][$lang]))
							{
								$lang = end(array_keys($avLanguages[$l[0]]));
							}
							$avLanguages[$l[0]][$lang]['selected'] = true;
							$sel = $lang;
							$found = true;
							break;
						}
					}
				}
				if($found)
					break;
			}
			if(!$found && array_key_exists(substr(GV_default_lng,0,2),$avLanguages))
			{
				if(!isset($avLanguages[substr(GV_default_lng,0,2)][GV_default_lng]))
				{
					define('GV_default_lng', end(array_keys($avLanguages[substr(GV_default_lng,0,2)])));
				}
				$avLanguages[substr(GV_default_lng,0,2)][GV_default_lng]['selected'] = true;
				$sel = GV_default_lng;
			}
		}
		if($sel == '')
		{
			$key = end(array_keys($avLanguages));
			$lang = end(array_keys($avLanguages[$key]));
			$avLanguages[$key][$lang]['selected'] = true;
			$sel = $lang;
		}
		$session->locale = $sel;
		
		if(($session->isset_cookie('locale') && $session->get_cookie('locale') != $sel) || !$session->isset_cookie('locale'))
			$session->set_cookie("locale",$sel,0,false);
		
		$sel = explode('_',$sel);
		
		$session->usr_i18n = $sel[0];
		$session->usr_l10n = $sel[1];
		
		return $avLanguages;
	}
	

	public static function setPrefs($prop, $value, $usr_id = false)
	{
		$session = session::getInstance();
		if(!$usr_id)
			$usr_id = $session->usr_id;
		
		$user = self::getInstance($usr_id);
		
		if(isset($user->_prefs[$prop]) && $user->_prefs[$prop] === $value)
			return $value;
			
		$ok = true;
		
		if(isset(self::$avalaible_values[$prop]))
		{
			$ok = false;
			if(in_array($value,self::$avalaible_values[$prop]))
				$ok = true;
		}
		
		if($ok)
		{
			$user->_prefs[$prop] = $value;
			$user->update_pref($prop,$value);
		}
		$cache = cache_user::getInstance();
		$cache->set($usr_id, $user);
		
		return $user->_prefs[$prop];
		
	}
	
	public static function getPrefs($prop,$usr_id = false)
	{
		
		$session = session::getInstance();
		if(!$usr_id)
			$usr_id = $session->usr_id;
		
		$user = self::getInstance($usr_id);
	
		if(!isset($user->_prefs[$prop]))
		{
			$user->_prefs[$prop] = null;
			$user->update_pref($prop,null);
		}
		
		
		return $user->_prefs[$prop];
		
	}

	public static function updateClientInfos($app_id)
	{
		
		$session = session::getInstance();
		if(!isset($session->usr_id) || !isset($session->ses_id))
			return;
		
		$ses_id = $session->ses_id;
		$usr_id = $session->usr_id;
		
		$appName = array(
			'1'	=>	'Prod',
			'2'	=>	'Client',
			'3'	=>	'Admin',
			'4'	=>	'Report',
			'5'	=>	'Thesaurus',
			'6'	=>	'Compare',
			'7'	=>	'Validate',
			'8'	=>	'Upload',
			'9'	=>	'API'
		);
		
		$conn = connection::getInstance();
		
		if(isset($appName[$app_id]))
		{
			
			$sql = 'SELECT dist_logid FROM cache WHERE session_id="'.$conn->escape_string($ses_id).'"';
			if($rs = $conn->query($sql))
			{
				
				if($row = $conn->fetch_assoc($rs))
				{
					
					$logs = unserialize($row['dist_logid']);
					
					$logs = !is_array($logs) ? array() : $logs ;
					
					$sbas_ids = array_keys($logs);
					
					foreach($sbas_ids as $sbas_id)
					{
						if(isset($logs[$sbas_id]))
						{
							$connSbas = connection::getInstance($sbas_id);
							if($connSbas)
							{
								$sql = 'SELECT appli FROM log WHERE id = "'.$connSbas->escape_string($logs[$sbas_id]).'"';
								if($rs3 = $connSbas->query($sql))
								{
									if($row3 = $connSbas->fetch_assoc($rs3))
									{
										$applis = unserialize($row3['appli']);
										
										if(!in_array($app_id,$applis))
										{
											$applis[] = $app_id;
										}
										
										$sql = 'UPDATE log SET appli="'.$connSbas->escape_string(serialize($applis)).'" WHERE id="'.$connSbas->escape_string($logs[$sbas_id]).'"';
										$connSbas->query($sql);
									}
									$connSbas->free_result($rs3);
								}
							}
						}
					}
				}
				$conn->free_result($rs);
			}
		}
		
		
		
		if($conn)
		{
			$theclient = browser::getInstance();
			$appinf["date"] =  date("d/m/Y G:i:s");
			$appinf["ip"] = $theclient->getIP();
			$appinf["usrid"] = $usr_id;
			$ph_session = phrasea_open_session($ses_id,$usr_id);
			$appinf["db"] = array();
			foreach ($ph_session["bases"] as $abas)
				$appinf["db"][]= $abas["sbas_id"];
			$appinf["info"]  = $theclient->getPlatform() . ' / ' . $theclient->getBrowser().'   ('.($session->isset_cookie('screen') ? $session->get_cookie('screen') : 'unknown') .')';
			$appinf["usr"] = null;	
			$sql = "SELECT usr_nom,usr_prenom,usr_mail,societe,tel FROM usr WHERE usr_id='".$conn->escape_string($usr_id)."'" ;
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
					$appinf["usr"]  = $row;
				$conn->free_result($rs);
			} 
			$sql = "SELECT app FROM cache WHERE session_id='".$conn->escape_string($ses_id)."'" ;
			$apps = array();
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
					$apps  = unserialize($row['app']);
				$conn->free_result($rs);
			}
	
			if(!in_array($app_id,$apps))
				$apps[] = $app_id;
			
				$sql = "UPDATE cache SET app='".$conn->escape_string(serialize($apps))."',appinf='".$conn->escape_string(serialize($appinf))."' WHERE session_id='".$conn->escape_string($ses_id)."'";
	
			$conn->query($sql);
		}
			return;
	}
	
	public static function get_sys_admins()
	{
		$sql = 'SELECT usr_id, usr_login FROM usr WHERE create_db="1"';
		
		$conn = connection::getInstance();
		
		$users = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
				$users[$row['usr_id']] = $row['usr_login'];
				
			$conn->free_result($rs);
		}
		
		return $users ;
	}
	
	public static function get_simple_users_list()
	{
		$sql = "SELECT usr_id, usr_login FROM usr 
				WHERE usr_login NOT LIKE '(#%' 
				AND invite='0' 
				AND usr_login != 'autoregister' 
				AND usr_login != 'invite' 
				AND create_db != '1' 
				ORDER by usr_login ASC";
		
		$conn = connection::getInstance();
		
		$users = array();
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
				$users[$row['usr_id']] = $row['usr_login'];
				
			$conn->free_result($rs);
		}
		
		return $users ;
				
	}
	
	public static function set_sys_admins($admins)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		
		$sql = "UPDATE usr SET create_db='0' WHERE create_db='1' AND usr_id != '".$session->usr_id."'";
		
		if($rs = $conn->query($sql))
		{
			$sql = "UPDATE usr SET create_db='1' WHERE usr_id IN (".implode(',', $admins).")";
			
			if($conn->query($sql))
				return true;
		}
		
		return false;
	}
	public static function reset_sys_admins_rights()
	{
		$conn = connection::getInstance();
		$users = self::get_sys_admins();
		
		$sql = "SELECT * FROM sbas";
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				
				foreach($users as $usr_id=>$value)
				{
					$sql = "REPLACE INTO sbasusr (sbas_id,usr_id,bas_manage,bas_modify_struct,bas_modif_th,bas_chupub) VALUES ('".$conn->escape_string($row['sbas_id'])."','".$conn->escape_string($usr_id)."','1','1','1','1')";
					$conn->query($sql);
					
					$sql = "SELECT * FROM bas WHERE sbas_id = '".$conn->escape_string($row['sbas_id'])."'";
					if($rsB = $conn->query($sql))
					{
						while($rowB = $conn->fetch_assoc($rsB))
						{
							$sql = "REPLACE INTO basusr " .
								" (base_id,usr_id,canpreview,canhd,canputinalbum,candwnldhd,candwnldsubdef,candwnldpreview,cancmd,canadmin,actif,canreport,canpush,creationdate,canaddrecord,canmodifrecord,candeleterecord,chgstatus,imgtools,manage,modify_struct,bas_manage,bas_modify_struct)" .
								" VALUES " .
								" ('".$conn->escape_string($rowB['base_id'])."','".$conn->escape_string($usr_id)."','1','1','1','1','1','1','1','1','1','1','1',now(),'1','1','1','1','1','1','1','1','1')";
							$conn->query($sql);
						}
					}
					self::clear_cache($usr_id);
				}
			}
		}
		return;
												
	}
	
	public static function get_locale($usr_id)
	{
		$conn = connection::getInstance();
		
		$locale = GV_default_lng;
		
		$sql = "SELECT locale FROM usr WHERE usr_id = '".$usr_id."'";
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$locale = $row['locale'];
			}
		}
		return $locale;
	}
	
	public static function create_special($usr_login)
	{
		$ret = false;
		try{
			$conn = connection::getInstance();
			
			$user = new user();
			
			$user->password = $usr_login;
			$user->login = $usr_login;
			$user->email = '';
			$user->superu = false;
			$user->is_admin = false;
			
			$id=$user->save();
			$ret = self::getInstance($id);
		}
		catch(Exception $e)
		{
			
		}
		return $ret;
	}
	
}