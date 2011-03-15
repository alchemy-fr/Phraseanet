<?php
class p4publi
{
	public static function getPublications($ssel_id)
	{
		$conn = connection::getInstance();
		
		$published = array();
		
		if($ssel_id)
		{
			$sql = 'SELECT publi_id FROM published WHERE ssel_id = "'.$conn->escape_string($ssel_id).'"';
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
					$published[] = $row['publi_id'];
				$conn->free_result($rs);
			}
			
		}	
		
		return $published;
	}
	
	public static function getForm($ssel_id = false)
	{
		$conn = connection::getInstance();
		
		$error = '';
		
		if($ssel_id)
		{
			$sql = 'SELECT count(sselcont_id) as n FROM sselcont WHERE ssel_id ="'.$conn->escape_string($ssel_id).'"';
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
					if($row['n'] == '0')
						$error = _('panier :: vous ne pouvez publier un panier vide');
				$conn->free_result($rs);
			}
		}
		
		ob_start(null, 0);
		
		$sql = 'SELECT public, pub_restrict FROM ssel WHERE ssel_id = "'.$conn->escape_string($ssel_id).'"';
		$public = $pub_restrict = '0';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$public = $row['public'];
				$pub_restrict = $row['pub_restrict'];
			}
		}
		
		?>
		<div><?php echo _('publi::Choisir les publications souhaitees : ');?></div>
		<form name="pub_choice" id="pub_choice">
				<div><input <?php echo ($public == '1' ? 'checked="checked"' : '' )?> name="publi[]" value="default" id="publi_default" type="checkbox"/> <label for="publi_default"><?php echo _('publi::Publication interne');?></label></div>
				<div><input <?php echo (($pub_restrict == '0' && $public == '1') ? 'checked="checked"' : '' )?> name="options[default]" value="HD" style="margin-left:20px" type="checkbox" id="publi_default_hd"/> <label for="publi_default_hd" style="font-style:italic;"><?php echo _('Autoriser le telechargement');?></label></div>
		<?php 
			$publis = self::getPersonnal();
			$published =  self::getPublications($ssel_id);
			

			
			foreach($publis as $k=>$p)
			{
				?>
				<div>
					<input <?php echo ( in_array($k,$published) ? 'checked="checked"' : '' )?> type="checkbox" name="publi[]" id="publi_<?php echo $k;?>" value="<?php echo $k;?>"/>
					<label for="publi_<?php echo $k;?>"><?php echo $p['name'];?></label>
					<a href="#" onclick="test_publi(<?php echo $k;?>);return false;">tester</a> <a href="#" onclick="delete_publi(<?php echo $k;?>);return false;">supprimer</a>
				</div>
				<?php 
			}
		?>
		</form>
		<div id="publi_presets" class="ui-corner-all">
			<?php echo _('publi::Ou ajouter une publication');?>
			<select name="new_preset" onchange="publi_preset(this)">
				<option value=""><?php echo _('publi::type');?></option>
				<?php 
				$publis = self::getAvalaible();
				$opts = '';
				$n = 0;
				foreach($publis as $p=>$f)
				{
					$n++;
					?>
					<option value="<?php echo $p;?>"><?php echo $p;?></option>
					<?php 
					$opts .= '<form id="new_publi_preset_'.$p.'" method="post" action="">
								<input type="hidden" value="'.$p.'" name="pname"/>
								<input type="hidden" value="'.$ssel_id.'" name="ssel"/>
							 	<div style="display:none;" class="publi_opts" id="publi_opts_'.$p.'">';
					foreach($f as $k=>$u)
					{
						$opts .='<div><input class="field" tabindex="'.$n.'" type="'.($k=='password'?'password':'text').'" value="" name="'.$k.'"/>'.$u.'</div>';
					}
			    	$opts .= '<div style="text-align:right;">
										<span class="error"></span>
										<input type="button" value="'._('boutton::annuler').'" onclick="publi_preset_cancel();"/>
										<input type="button" value="'._('boutton::ajouter').'" onclick="publi_preset_add(\''.$p.'\');"/>
									</div>
								</div>
							</form>';
				}
				?>
			</select>
			
			<?php echo $opts;?>
		</div>
		<?php 
		
		return array('error'=>$error,'datas'=>ob_get_clean());
	}
	
	public static function getAvalaible()
	{
		$ret = array();
		$conn = connection::getInstance();
		
		$path = dirname(__FILE__) . '/publi/';
		
		if(($dir = opendir( $path )) !== false)
		{
			while(($file=readdir($dir)) !== false)
			{
				$substr = substr($file,0,-10);

				$classname = 'publi_'.$substr;
				
				if(is_file($path.$file) && trim($substr) !== '')
				{
					$pub = new $classname();
					$ret[$substr] = $pub->requiredFields();
				}
			}
			
		}
		return $ret;
	}
	
	
	public static function getPersonnal()
	{
		$ret = array();
		$conn = connection::getInstance();
		$session = session::getInstance();
		$sql = 'SELECT * FROM publi_settings WHERE usr_id=NULL OR usr_id="'.$conn->escape_string($session->usr_id).'"';
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$ret[$row['publi_id']] = $row;
			}
			$conn->num_rows($rs);
		}
		
		return $ret;
	}
	
	public static function testPubliPreset($publi_id)
	{
		$publis = self::getPersonnal();

		if(!isset($publis[$publi_id]))
			return false;
		
		$classname = 'publi_'.$publis[$publi_id]['type'];
		
		$publi = new $classname();
		
		return $publi->test($publis[$publi_id]);
		
	}
	
	public static function deletePubliPreset($publi_id, $ssel_id)
	{
		$conn = connection::getInstance();
		
		$publis = self::getPersonnal();

		if(!isset($publis[$publi_id]))
			return false;
		
		$ret = false;	
		
		$sql = 'DELETE FROM publi_settings WHERE publi_id="'.$conn->escape_string($publi_id).'"';
		if($conn->query($sql))
			$ret = true;
		
		$sql = 'DELETE FROM published WHERE publi_id="'.$conn->escape_string($publi_id).'"';
		$conn->query($sql);
		
		
		$form = self::getForm($ssel_id);
		return p4string::jsonencode(array('status'=>$ret, 'text'=>$form['datas']));
		
	}
	
	function addPubliPreset($serialdatas,$ssel_id)
	{
		$datas = array();
		$serialdatas = explode('&',urldecode($serialdatas));
		foreach($serialdatas as $k=>$d)
		{
			$serialdatas[$k] = explode('=',$d);
			if(count($serialdatas[$k]) == 2)
				$datas[$serialdatas[$k][0]] = $serialdatas[$k][1];
		}
		
		$ret = array('status'=>0,'text'=>'');
		
		if(isset($datas['pname']) && trim($datas['pname']) !== '')
		{
			$classname = 'publi_'.$datas['pname'];
			try
			{
				$publisher = new $classname();
				foreach($publisher->requiredFields() as $k=>$f)
				{
					if(!isset($datas[$k]))
					{
						$ret['status'] = -3;	
						$ret['text'] = _('phraseanet:: required fields');
						break;
					}
					$ret['status'] = 1;
				}
			}catch(Exception $e){
				
			}
			
			if($ret['status'] === 1)
			{
				if($publisher->test($datas))
				{
					if($publisher->savePreset($datas))
					{
						$form = self::getForm($ssel_id);
						if($form['error'] == '')
							$ret['text'] = $form['datas'];
						else
						{
							$ret['status'] = -1;
							$ret['text'] = _('phraseanet:: error saving datas');
						}
					}
					else
					{
						$ret['status'] = -1;
						$ret['text'] = _('phraseanet:: error saving datas');
					}
				}
				else
				{
					$ret['status'] = -2;	
					$ret['text'] = _('phraseanet:: error write-test datas');
				}
			}
		}
		
		return p4string::jsonencode($ret);
	}

	function publishBasket($ssel_id,$status)
	{
		
		$session = session::getInstance();
		if(!($ph_session = phrasea_open_session($session->ses_id,$session->usr_id)))
			return;
		
		$conn = connection::getInstance();
		
		$ret = 'error';
		if($conn)
		{
			$publi = $options = $unpubli = array();
			
		
			$sql = 'SELECT DISTINCT s.publi_id FROM publi_settings s, published p WHERE s.usr_id = null OR s.usr_id="'.$conn->escape_string($session->usr_id).'" AND s.publi_id = p.publi_id AND p.ssel_id="'.$conn->escape_string($ssel_id).'"';

			$unpubli['default'] = 'default';
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
					$unpubli[$row['publi_id']] = $row['publi_id'];
				$conn->free_result($rs);
			}
			
			$status = explode('&',urldecode($status));
			foreach($status as $s)
			{
				$s = explode('=',$s);
				if(count($s) == 2)
				{
					if($s[0] == 'publi[]')
						$publi[] = $s[1];
					if(isset($unpubli[$s[1]]))	
						unset($unpubli[$s[1]]);
					if(substr($s[0],0,8) == 'options[')
					{
						if(!isset($options[substr($s[0],8,-1)]))
							$options[substr($s[0],8,-1)] = array();
							
						$options[substr($s[0],8,-1)][$s[1]] = 1;
					}	
				}
			}
		
			$sql = "SELECT distinct sbasusr.bas_chupub,sbasusr.sbas_id
					FROM (usr NATURAL JOIN basusr)
					INNER JOIN bas ON basusr.base_id=bas.base_id
					INNER JOIN sbasusr on sbasusr.usr_id='" . $conn->escape_string($session->usr_id). "' and sbasusr.sbas_id=bas.sbas_id
					WHERE usr.usr_id='" . $conn->escape_string($session->usr_id). "' and actif=1";
			
			$usrRight = array();
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					if($row["bas_chupub"] == "1")
						$usrRight[] = $row["sbas_id"];
				}
				$conn->free_result($rs);
			}
			
			
			$isOk = false;
			
			$sql = "SELECT distinct b.sbas_id FROM bas b, sselcont c, ssel s WHERE s.usr_id = '".$conn->escape_string($session->usr_id)."' AND s.ssel_id='".$conn->escape_string($ssel_id)."' AND c.ssel_id = s.ssel_id AND b.base_id = c.base_id";
			
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					if(!in_array($row['sbas_id'],$usrRight))
					{
						$isOk = false;
						break;
					}
					$isOk = true;
				}
				$conn->free_result($rs);
			}
			
			
			
			if(count($publi) == 0 && count($unpubli) == 0)
				return p4string::jsonencode(array('status'=>'1'));
				
			if($isOk)
			{
				foreach($publi as $pub)
				{
					$o = false;
					if(isset($options[$pub]))
						$o = $options[$pub];
						
					if(self::publi($ssel_id, $pub,$o))
					{
						if($ret == 'error')
							$ret = '1';
					}
					else 
						$ret = '0';
					if(isset($unpubli[$pub]))
						unset($unpubli[$pub]);
						
				}
				foreach($unpubli as $pub)
				{
					if(self::unpublish($ssel_id, $pub))	
					{
						if($ret == 'error')
							$ret = '1';
					}
					else 
						$ret = '0';
				}
				
			} 
		}
		
		return p4string::jsonencode(array('status'=>$ret));
	}
	
	public static function unpublish($ssel_id, $pub_id)
	{
		$session = session::getInstance();
		$conn = connection::getInstance();
		$ret = false;
		if($pub_id == 'default')
		{
			
			$sql = 'UPDATE ssel SET public="0", pub_restrict="0" WHERE ssel_id = "'.$conn->escape_string($ssel_id).'"';
			
			
			if($conn->query($sql))
      {
        $cache_basket = cache_basket::getInstance();
        $cache_basket->delete($session->usr_id, $ssel_id);
        
				$ret = true;
      }
			$sql = 'SELECT b.*, c.base_id, c.record_id FROM sselcont c, bas b WHERE c.ssel_id = "'.$conn->escape_string($ssel_id).'" AND c.base_id = b.base_id ORDER BY sbas_id ASC';

			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					answer::logEvent(phrasea::sbasFromBas($row['base_id']),$row['record_id'],'unpublish','public','');
				}
			}
			
//			self::clear_feeds_cache($ssel_id);
			
			$sql = 'DELETE FROM sselnew WHERE ssel_id="'.$conn->escape_string($ssel_id).'"';
			$conn->query($sql);	
		}
		else
		{
			$sql = 'SELECT * FROM publi_settings WHERE publi_id="'.$conn->escape_string($pub_id).'"';
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$classname = 'publi_'.$row['type']; 
					$obj = new $classname();
					if($obj->unpublish($row, $ssel_id))
					{
						$ret = true;
					
						$sql = 'DELETE FROM published WHERE ssel_id = "'.$conn->escape_string($ssel_id).'" AND publi_id = "'.$conn->escape_string($pub_id).'"';
						$conn->query($sql);
					}
				}
			}
		}
	
		return $ret;
	}
	
//	private static function clear_feeds_cache($ssel_id)
//	{
//		
//		$feed_cache = cache_feed::getInstance();
//		$conn = connection::getInstance();
//		$feed_cache->delete('_internalpubli_'.$session->usr_id);
//		
//		$sql = 'SELECT distinct usr_id FROM sselnew WHERE ssel_id = "'.$ssel_id.'"';
//		
//		if($rs = $conn->query($sql))
//		{
//			while($row = $conn->fetch_assoc($rs))
//			{
//				$feed_cache->delete('_internalpubli_'.$row['usr_id']);
//			}
//			$conn->free_result($rs);
//		}
//		
//		return;
//	}
	
	private static function publi($ssel_id, $pub_id, $options)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		$ret = false;
		if($pub_id == 'default')
		{	
			
			$sql = 'UPDATE ssel SET public="1", pub_restrict="1", pub_date=NOW() WHERE ssel_id = "'.$conn->escape_string($ssel_id).'"';
			
			if(isset($options['HD']) && $options['HD'] == '1')
			{
				$sql = 'UPDATE ssel SET public="1", pub_restrict="0", pub_date=NOW() WHERE ssel_id = "'.$conn->escape_string($ssel_id).'"';
			}
			
			if($conn->query($sql))
      {
        $cache_basket = cache_basket::getInstance();
        $cache_basket->delete($session->usr_id, $ssel_id);

				$ret = '1';
      }
			$sql = 'SELECT b.*, c.base_id, c.record_id FROM sselcont c, bas b WHERE c.ssel_id = "'.$conn->escape_string($ssel_id).'" AND c.base_id = b.base_id ORDER BY sbas_id ASC';

			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					answer::logEvent(phrasea::sbasFromBas($row['base_id']),$row['record_id'],'publish','public','');
				}
			}
			
			$params = array(
				'from'		=> $session->usr_id
				,'ssel_id'	=> $ssel_id
			);
			
			$events_mngr = eventsmanager::getInstance();
			$events_mngr->trigger('__INTERNAL_PUBLI__',$params);
			
			$sql = 'INSERT INTO sselnew (SELECT DISTINCT null, "'.$conn->escape_string($ssel_id).'", usr_id FROM basusr WHERE base_id IN (SELECT distinct base_id FROM sselcont WHERE ssel_id="'.$conn->escape_string($ssel_id).'"))';
			$ret = $conn->query($sql);	
			
			
//			self::clear_feeds_cache($ssel_id);
		}
		else
		{
			$sql = 'SELECT * FROM publi_settings WHERE publi_id="'.$pub_id.'"';
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					$classname = 'publi_'.$row['type']; 
					$obj = new $classname();
					$ret = $obj->publish($row, $ssel_id);
				}
			}
		}
		
		return $ret;
	}
	
	function prepare($ssel_id)
	{
		
		$titre = $desc = '';
		$conn = connection::getInstance();
		
		$sql = 'SELECT * FROM ssel WHERE ssel_id="'.$conn->escape_string($ssel_id).'"';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$titre = $row['name'];
				$desc = $row['descript'];
			}
			$conn->free_result($rs);
		}
		
		$images = '';
		
		$sql = 'SELECT c.*, b.sbas_id FROM sselcont c, bas b WHERE c.base_id = b.base_id AND ssel_id="'.$conn->escape_string($ssel_id).'"';
		
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$connSbas = connection::getInstance($row['sbas_id']);
				
				if($connSbas)
				{
					$sql2 = 'SELECT sha256 FROM record WHERE record_id = "'.$connSbas->escape_string($row['record_id']).'" ';
								
					if($rs2 = $connSbas->query($sql2))
					{
						if($row2 = $connSbas->fetch_assoc($rs2))
						{
							$sha256 = $row2['sha256'];
						}
						$connSbas->free_result($rs2);
					}
					
					if(isset($sha256))
					{
						$url = GV_ServerName."document/".$row['base_id']."/".$row['record_id']."/".$sha256."/";
						$images .= '<a href="'.$url.'view/"><img src="'.$url.'" title=""/></a> ';
					}
				}
			}
			$conn->free_result($rs);
		}
		
		$desc .= $images;
		
		return array('desc'=>$desc,'titre'=>$titre);
	}
	
	function save($ssel_id, $publi_id, $post_id)
	{
		$conn = connection::getInstance();
		
		$sql = 'INSERT INTO published (ssel_id, publi_id, post_id) VALUES ("'.$conn->escape_string($ssel_id).'", "'.$conn->escape_string($publi_id).'","'.$conn->escape_string($post_id).'")';
		if($conn->query($sql))
		{
			return true;
		}
		return false;
	}
}