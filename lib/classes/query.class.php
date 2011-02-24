<?php

class query
{
	
	private $query = '';
	private $queries = array();
	private $colls = array();
	private $qp = array();
	private $answers = array();
	private $needthesaurus = array();
	private $indep_treeq = array();
	private $options = false;
	private $arrayq = array();
	private $results = array();
	
	function __construct($options)
	{
		if(!isset($options['type']))
			$options['type'] = 0;
		if(!isset($options['bases']))
			$options['bases'] = array();
		if(!isset($options['champs']))
			$options['champs'] = array();
		if(!isset($options['status']))
			$options['status'] = array();
		if(!isset($options['date']))
			$options['date'] = array('minbound'=>'','maxbound'=>'','field'=>'');
		if(!isset($options['date']['minbound']))
			$options['date']['minbound'] = '';
		if(!isset($options['date']['maxbound']))
			$options['date']['maxbound'] = '';
		if(!isset($options['date']['field']))
			$options['date']['field'] = '';
			
		$reset_fields = false;
		foreach($options['champs'] as $k=>$c)
			if(trim($c) === 'phraseanet--all--fields')
				$reset_fields = true;
		
		if($reset_fields)
			$options['champs'] = array();	
			
		foreach($options['status'] as $k=>$s)
		{
			if(isset($s['off']))
			{
				$off = array();
				foreach($s['off'] as $doff)
					$off = array_merge($off,explode('_',$doff));
			
				$options['status'][$k]['off'] = $off;	
			}
			if(isset($s['on']))
			{
				$on = array();
				foreach($s['on'] as $don)
					$on = array_merge($on,explode('_',$don));
			
				$options['status'][$k]['on'] = $on;	
			}
		}
		
		$this->options = $options;
			
	}
	
	
	private static function  proposalsToHTML($proposals)
	{
	
		$html = '';
		$b = true;
		foreach($proposals["BASES"] as $zbase)
		{
			if((int)(count($proposals["BASES"]) > 1) && count($zbase["TERMS"])>0)
			{
				$style = $b? 'style="margin-top:0px;"':'';
				$b = false;
				$html .= "<h1 $style>" . sprintf(_('reponses::propositions pour la base %s'), $zbase["NAME"]) . "</h1>";
			}
			$t = true;
			foreach($zbase["TERMS"] as $path=>$props)
			{
				$style = $t? 'style="margin-top:0px;"':'';
				$t = false;
				$html .= "<h2 $style>" . sprintf(_('reponses::propositions pour le terme %s'), $props["TERM"]) . "</h2>";
				$html .= $props["HTML"];
			}
		}
		$html .= '';
		return($html);
	}

	
	
	public function proposals()
	{
		if(isset($this->qp['main']))
		{
			$proposals = p4string::MakeString(self::proposalsToHTML($this->qp['main']->proposals),"JS");
			if(trim($proposals) !== '')
			return "<div style='height:0px; overflow:hidden'>".p4string::MakeString($this->qp['main']->proposals["QRY"],"JS")
				."</div><div class='proposals'>".$proposals."</div>";
		}
		return false;
	}
	
	public static function getPrevTrain($pos=0)
	{
		$session = session::getInstance();
		$train = '' .
			'<div id="PREVIEWCURRENTCONT" class="PNB10">' .
				'<ul>';
		$perPage = 50;
		
		$N = floor(( $pos ) / ($perPage - 2)) + 1;
		$index = ($N - 1) * ($perPage - 3) +1;  
		$index = ($index < 0) ? 0 : $index; 
		
		phrasea_open_session($session->ses_id,$session->usr_id);
		
		$results = phrasea_fetch_results($session->ses_id, $index,$perPage, true);
		
		
		$rs = array();
		if(isset($results['results']) && is_array($results['results']))
			$rs = $results['results'];
			
		$i = ($index <= 0) ? 0 : ($index - 1);
		
		$array_icons = array(
			'flash'		=> '',
			'document'	=> '',
			'image'		=> '',
			'video'		=> '',
			'audio'		=> '',
			'unknown'	=> ''
		);
		
		if(user::getPrefs('doctype_display') == '1')
		{
			$array_icons = array(
				'flash'		=> '<img src="/skins/icons/icon_flash.gif" />',
				'document'	=> '<img src="/skins/icons/icon_document.gif" />',
				'image'		=> '<img src="/skins/icons/icon_image.gif" />',
				'video'		=> '<img src="/skins/icons/icon_video.gif" />',
				'audio'		=> '<img src="/skins/icons/icon_audio.gif" />',
				'unknown'	=> ''
			);
		}
		
		
		if($rs)
		{
			foreach($rs as $irec=>$data)
			{
				$sd = answer::getThumbnail($session->ses_id,$data['base_id'],$data['record_id']);
			
				if($sd['w']>$sd['h'])
					$style='width:65px;top:'.round((66-(65/($sd['w']/$sd['h'])))/2).'px;';
				else
					$style='height:65px;top:0;';
				
				$class = '';
				if($pos == $i)
					$class = 'selected';
			
				$duration = '';

				if($sd['type'] == 'video'){
					$duration = answer::get_duration($data["xml"]);
					if($duration == '00:00')
						$duration = '';
				}
					
				$train .= '<li class="prevTrainCurrent current'.$i.' '.$class.'" style="">
					<div class="doc_infos">'.$array_icons[$sd['type']].'<span class="duration">'.$duration.'</span></div>
					<img jsargs="RESULT|'.$i.'|" class="openPreview" src="'.$sd['thumbnail'].'" style="'.$style.'margin:7px;position:relative;"/>
				</li>';
				$i++;
			}
		}
			
		$train .= '</ul>' .
			'</div>'.
			'<div class="cont_infos">'.
				'<div>' .
					'<img src="/skins/icons/light_left.gif" style="margin-right:10px;" onclick="getPrevious();"/>' .
					'<img src="/skins/icons/light_right.gif" style="margin-left:10px;" onclick="getNext();"/><br/>' .
					'<span onclick="startSlide()" id="start_slide"> '._('preview:: demarrer le diaporama').' </span>' .
					'<span onclick="stopSlide()" id="stop_slide"> '._('preview:: arreter le diaporama').' </span>' .
				'</div>' .
			'</div>' .
//			'<div><img onclick="getPrevious();" style="margin-right: 10px;" src="http://utf8.romain/skins/icons/light_left.gif"/><img onclick="getNext();" style="margin-left: 10px;" src="http://utf8.romain/skins/icons/light_right.gif"/><br/><span id="start_slide" onclick="startSlide()"> D�marrer </span><span id="stop_slide" onclick="stopSlide()" style="display: none;"> Arr�ter </span></div></div>'.
			'<div id="PREVIEWTOOL"></div>' .
			'';
		return $train;
	}
	
	function microtime_float()
	{
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}
	
	function results($query,$page=0)
	{
		
		$session = session::getInstance();
		$perPage = user::getPrefs('images_per_page');
		
		$th_size = user::getPrefs('images_size');
		
		$courcahnum = 0 ;
		
		if($page == 0)
		{
			self::addQuery($query);
			$start = self::microtime_float();
			self::query();
			$stop = self::microtime_float();
			$page=1;
		}
		
		$courcahnum = (($page-1)*$perPage) ;
		
		$start = self::microtime_float();
		$results = phrasea_fetch_results($session->ses_id, (($page-1)*$perPage)+1, $perPage, true, "[[em]]", "[[/em]]");

		$rs = array();
		if(isset($results['results']) && is_array($results['results']))
			$rs = $results['results'];
		
		$stop = self::microtime_float();

		$xml = new DomDocument();
		
		$basesettings = phrasea::load_settings($session->locale);
	
		
		$rsScreen = array();
		
		$conn = connection::getInstance();
		
		$user = user::getInstance($session->usr_id);
		
		$dstatus = status::getDisplayStatus();
			
		$array_icons = array(
			'flash'		=> '',
			'document'	=> '',
			'image'		=> '',
			'video'		=> '',
			'audio'		=> '',
			'unknown'	=> ''
		);
		
		if(user::getPrefs('doctype_display') == '1')
		{
			$array_icons = array(
				'flash'		=> '<img src="/skins/icons/icon_flash.gif" />',
				'document'	=> '<img src="/skins/icons/icon_document.gif" />',
				'image'		=> '<img src="/skins/icons/icon_image.gif" />',
				'video'		=> '<img src="/skins/icons/icon_video.gif" />',
				'audio'		=> '<img src="/skins/icons/icon_audio.gif" />',
				'unknown'	=> ''
			);
		}
		
		
		
		if($rs && isset($rs['results']))
			$rs = $rs['results'];
			
		if($rs)
		{
			foreach($rs as $irec=>$data)
			{
				$rsScreen[$irec] = array(
					'title'=>'',
					'status'=>'',
					'type'=>'',
					'thumb'=>'',
					'caption'=>'',
					'preview'=>'',
					'imgclass'=>'',
					'rollover_gif'=>'',
					'imgstyle'=>'',
					'sha256'=>false,
					'infos'=>'',
					'base_id'=>$data['base_id'],
					'record_id'=>$data['record_id'],
					'number'=>$courcahnum,
					'duration'=>false,
					'sbas'=>'',
					'share'=>'',
					'logo'=>collection::getLogo($data['base_id'], true),
					'grouping'=>false
				);
				
				$rsScreen[$irec]['sbas'] = $sbas_id = phrasea::sbasFromBas($data["base_id"]);
				$rsScreen[$irec]['grouping'] = $is_grouping = ($data["parent_record_id"]!='0'?true:false);
				
				$ident = $data["base_id"]."_".$data["record_id"];

				$base_id = $data["base_id"]; 
				
				$thumbnail = answer::getThumbnail($session->ses_id, $data["base_id"], $data["record_id"],GV_zommPrev_rollover_clientAnswer);
		
				
				if($thumbnail['sha256'])
					$rsScreen[$irec]['sha256'] = $thumbnail['sha256'];
				
				$title = $exifinfos = $captions = '';
				if(isset($data['xml']))
				{
					$title = answer::format_title($sbas_id, $data["record_id"], $data['xml']);
					$exifinfos = answer::format_infos($data['xml'], $sbas_id, $data["record_id"],$thumbnail['type']);
					$captions = answer::format_caption($base_id, $data["record_id"],$data['xml']);
				}

				
	
					
				if(isset($data) && isset($data["status"]))
				{
					
					if(isset($dstatus[$sbas_id]))
					{			
						foreach($dstatus[$sbas_id] as $n=>$statbit)
						{
							if($statbit['printable'] == '0' && (!isset($user->_rights_bas[$data['base_id']]) || $user->_rights_bas[$data['base_id']]['chgstatus'] === false))
								continue;
								
							$d = ((int)$n)>>2;
							$m = 1<<((int)$n & 0x03);
							if($d>=0 && $d<=15)
							{
									
								$x = hexdec(substr($data["status"], 15-$d, 1));
								
								$source0 = "/skins/icons/spacer.gif";
								$style0 = "visibility:hidden;display:none;";
								$source1 = "/skins/icons/spacer.gif";
								$style1 = "visibility:hidden;display:none;";
								if($statbit["img_on"])
								{
									$source1 = $statbit["img_on"];
									$style1 = "visibility:auto;display:none;";
								}
								if($statbit["img_off"])
								{
									$source0 = $statbit["img_off"];
									$style0 = "visibility:auto;display:none;";
								}
								if($x & $m)
								{
									if($statbit["img_on"])
									{
										$style1 = "visibility:auto;display:inline;";
									}
								}
								else
								{
									if($statbit["img_off"])
									{
										$style0 = "visibility:auto;display:inline;";
									}
								}
								$rsScreen[$irec]['status'] .= ("<img style=\"margin:1px;".$style1."\" id=\"STAT_".$ident."_".$n."_1\" src=\"".$source1."\" title=\"".(isset($statbit["labelon"])?$statbit["labelon"]:$statbit["lib"])."\"/>");				
								$rsScreen[$irec]['status'] .= ("<img style=\"margin:1px;".$style0."\" id=\"STAT_".$ident."_".$n."_0\" src=\"".$source0."\" title=\"".(isset($statbit["labeloff"])?$statbit["labeloff"]:("non-".$statbit["lib"]))."\"/>");
							}
						}
					}
				}
							
					
				$ratio = $thumbnail["w"] / $thumbnail["h"];
						
				if($ratio > 1)
				{
					$cw = min(((int)$th_size),$thumbnail["w"]);
					$ch = $cw/$ratio;
					$pv = floor(($th_size-$ch)/2);
					$ph = floor(($th_size-$cw)/2);
					$imgStyle = 'width:'.$cw.'px;padding:'.$pv.'px '.$ph.'px;';
				}
				else
				{
					$ch = min(((int)$th_size),$thumbnail["h"]);
					$cw = $ch*$ratio;
					
					$pv = floor(($th_size-$ch)/2);
					$ph = floor(($th_size-$cw)/2);
					
					$imgStyle = 'height:'.$ch.'px;padding:'.$pv.'px '.$ph.'px;';
				}
				
				if($thumbnail["rollover"])
				{
					
					$ratio = $thumbnail['rollover_width'] / $thumbnail['rollover_height'];
							
					if($ratio > 1)
					{
						$cw = min(((int)$th_size),$thumbnail["rollover_width"]);
						$ch = $cw/$ratio;
						$pv = floor(($th_size-$ch)/2);
						$ph = floor(($th_size-$cw)/2);
						$rolloverStyle = 'width:'.$cw.'px;padding:'.$pv.'px '.$ph.'px;';
					}
					else
					{
						$ch = min(((int)$th_size),$thumbnail["rollover_height"]);
						$cw = $ch*$ratio;
						
						$pv = floor(($th_size-$ch)/2);
						$ph = floor(($th_size-$cw)/2);
						
						$rolloverStyle = 'height:'.$ch.'px;padding:'.$pv.'px '.$ph.'px;';
					}
					$rsScreen[$irec]['rollover_gif'] = array('src'=>$thumbnail["rollover"],'style'=>$rolloverStyle);
				}
					
				$isVideo = $isImage = $isAudio = $isDocument = false;
		
				$docType = $thumbnail['type'];
				$isVideo = $docType == 'video' ? true:false;
				$isAudio = $docType == 'audio' ? true:false;
				$isImage = $docType == 'image' ? true:false;
				$isDocument = $docType == 'document' ? true:false;
				
				$rsScreen[$irec]['type'] = $docType;
						
				if(!$isVideo && !$isAudio)
					$isImage = true;
				
				$duration = '';
				if($isVideo){
					$duration = answer::get_duration($data["xml"]);
					if($duration == '00:00')
						$duration = '';
				}
				elseif($isAudio){
					$duration = answer::get_duration($data["xml"]);
					if($duration == '00:00')
						$duration = '';
				}
				$rsScreen[$irec]['duration'] ='<div class="doc_infos">'.$array_icons[$docType].'<span class="duration">'.$duration.'</span></div>';
	
				$rsScreen[$irec]['preview'] = $preview = '';
				if(GV_zommPrev_rollover_clientAnswer)
				{
					$canprev = false;
	
					if(isset($user->_rights_bas[$data['base_id']]) && $user->_rights_bas[$data['base_id']]['canpreview']=='1')
						$canprev = true;
					$preview = answer::get_preview_rollover($data['base_id'],$data['record_id'],$session->ses_id,$canprev,$session->usr_id,$thumbnail['preview'],$thumbnail['type']);
					$rsScreen[$irec]['preview'] = trim($preview);						
				}
				
				$rsScreen[$irec]['thumb'] = $thumbnail["thumbnail"];
				$rsScreen[$irec]['caption'] = $captions;
				$rsScreen[$irec]['title'] = $title;
				$rsScreen[$irec]['imgclass'] = $thumbnail['imgclass'];
				$rsScreen[$irec]['imgstyle'] = $imgStyle;
				$courcahnum++;
				
				$rsScreen[$irec]['infos'] .= $exifinfos;
			
				$rsScreen[$irec]['share'] = '<a style="float:right;padding:0;margin:0;cursor:pointer;" class="contextMenuTrigger" id="contextTrigger_'.$data["base_id"]."_".$data["record_id"].'">&#9660;</a>
						 <table cellspacing="0" cellpadding="0" style="display:none;" id="answerContext_'.$data["base_id"]."_".$data["record_id"].'" class="contextMenu answercontextmenu">
							<tbody>
								<tr>
									<td>
										<div class="context-menu context-menu-theme-vista">';
				
				$user = user::getInstance($session->usr_id);
				
				if($user->_rights_bas[$data['base_id']]['canputinalbum'])
				{
					$rsScreen[$irec]['share'] .= '<div title="" class="context-menu-item">
														<div class="context-menu-item-inner" onclick="evt_add_in_chutier(\''.$data['base_id'].'\',\''.$data['record_id'].'\',false,this);return(false);">'._('action : ajouter au panier').'</div>
													</div>';
				}
				
				if($user->_rights_bas[$data['base_id']]['candwnldpreview'] || $user->_rights_bas[$data['base_id']]['candwnldhd'])
				{
					$rsScreen[$irec]['share'] .= '<div title="" class="context-menu-item">
														<div class="context-menu-item-inner" onclick="evt_dwnl(\''.$data['base_id'].'_'.$data['record_id'].'\',false,this);return(false);">'._('action : exporter').'</div>
													</div>';
				}
				
				$rsScreen[$irec]['share'] .= '<div title="" class="context-menu-item">
													<div class="context-menu-item-inner" onclick="evt_print(\''.$data['base_id'].'_'.$data['record_id'].'\');return(false);">'._('action : print').'</div>
												</div>';
				
				
				
				if($this->options['type'] == '0' && (GV_social_tools == 'all' || (GV_social_tools == 'publishers' && $user->_rights_sbas[$sbas_id]['bas_chupub']==true)))
				{
					$rsScreen[$irec]['share'] .= '<div title="" class="context-menu-item">
														<div class="context-menu-item-inner" onclick="shareThis(\''.$data['base_id'].'\',\''.$data['record_id'].'\');">'._('reponses:: partager').'</div>
													</div>';
				}
				
				$rsScreen[$irec]['share'] .= '</div>
											</td>
										</tr>
									</tbody>
								</table>';
			}
		}
		
		return array(
			'result'=>$rsScreen
			,'current_page'=>$page
			,'pages'=>ceil($session->prod['query']['nba']/$perPage)
			,'explain'=>self::explain()
		);
	}
	
	private function query()
	{
		
		$session = session::getInstance();
		phrasea_clear_cache($session->ses_id);
		$dst_logid= null;
		$dateLog = date("Y-m-d H:i:s"); 
		$nbanswers = 0;
		
		$conn = connection::getInstance();
		$sql2 = 'SELECT dist_logid FROM cache WHERE session_id="'.$conn->escape_string($session->ses_id).'"';
		if($rs2 = $conn->query($sql2))
		{
			if( $row2 = $conn->fetch_assoc($rs2) )
			{
				$dst_logid = unserialize($row2["dist_logid"]);				
			}				
			$conn->free_result($rs2);
		}
		foreach($this->queries as $sbas_id=>$qry)
		{
 			
			if($this->options['type'] == '1')
 				$this->results[$sbas_id] = phrasea_query2($session->ses_id, $sbas_id, $this->colls[$sbas_id], $this->arrayq[$sbas_id], GV_sit, (string)($session->usr_id) , false , PHRASEA_MULTIDOC_REGONLY );
 			else 
 				$this->results[$sbas_id] = phrasea_query2($session->ses_id, $sbas_id, $this->colls[$sbas_id], $this->arrayq[$sbas_id], GV_sit, (string)($session->usr_id) , false , PHRASEA_MULTIDOC_DOCONLY);

 			if($this->results[$sbas_id])
				$nbanswers += $this->results[$sbas_id]["nbanswers"];
			
			$conn2 = connection::getInstance($sbas_id);
			
			if($conn2)		
			{
				$sql2 = "SELECT * FROM uids WHERE name='QUEST'";
				if($rs2 = $conn2->query($sql2))
				{
					if( $conn2->num_rows($rs2)==0 )
					{
						$sql3 = "INSERT INTO uids (uid, name) VALUES (1, 'QUEST')" ;
						$conn2->query($sql3);
					}				
				}
				 
				$newid = $conn2->getId("QUEST");

				if(isset($dst_logid[$sbas_id]))
				{
//					$sql3  = "INSERT INTO quest (id, logid, date, askquest, nbrep, coll_id ) VALUES " ;
//					$sql3 .= "('".$conn2->escape_string($newid)."', '".$conn2->escape_string($dst_logid[$sbas_id])."','" . $conn2->escape_string($dateLog) . "', '".$conn2->escape_string($this->query)."', ".$conn2->escape_string($this->results[$sbas_id]["nbanswers"]).", '".$conn2->escape_string(implode(',',$this->colls[$sbas_id]))."')" ;
//					$conn2->query($sql3);	
					$sql3  = "INSERT INTO log_search (id, log_id, date, search, results, coll_id ) VALUES " ;
					$sql3 .= "(null, '".$conn2->escape_string($dst_logid[$sbas_id])."','" . $conn2->escape_string($dateLog) . "', '".$conn2->escape_string($this->query)."', ".$conn2->escape_string($this->results[$sbas_id]["nbanswers"]).", '".$conn2->escape_string(implode(',',$this->colls[$sbas_id]))."')" ;
					$conn2->query($sql3);	
				}
			}				
		}
		
		
		
		user::saveQuery($this->query);
		
		$prod_datas = $session->prod;
		$prod_datas['query']['nba'] = $nbanswers;
		
		$session->prod = $prod_datas;
		
		
	}
	
	
	private function singleParse($sbas)
	{
		$session = session::getInstance();
		$this->qp[$sbas] = new qparser($session->locale);
		$this->qp[$sbas]->debug = false;
		if($sbas == 'main')
			$simple_treeq = $this->qp[$sbas]->parsequery($this->query);
		else
			$simple_treeq = $this->qp[$sbas]->parsequery($this->queries[$sbas]);
			
		$this->qp[$sbas]->priority_opk($simple_treeq);
		$this->qp[$sbas]->distrib_opk($simple_treeq);
		$this->needthesaurus[$sbas] = false;

		$this->indep_treeq[$sbas] =  $this->qp[$sbas]->extendThesaurusOnTerms($simple_treeq, true, true, false);
		$this->needthesaurus[$sbas] = $this->qp[$sbas]->containsColonOperator($this->indep_treeq[$sbas]);
			
			
//		if($this->qp[$sbas]->containsColonOperator($simple_treeq))
//		{
//			$this->indep_treeq[$sbas] = $this->qp[$sbas]->extendThesaurusOnTerms($simple_treeq, true, true, false);
//			$this->needthesaurus[$sbas] = true;
//		}
//		else
//		{
//			$this->indep_treeq[$sbas] = $simple_treeq;
//		}
	}
	
	
	private function addQuery($query)
	{
		$qry = '';
		if(trim($query) != '')
		{
			$qry .= trim($query);
		}
		
		$ph_session = phrasea::bases();
		
		foreach($ph_session['bases'] as $base)
		{
			foreach($base['collections'] as $coll)
			{
				if(in_array($coll['base_id'],$this->options['bases']))
				{
					$this->queries[$base['sbas_id']] = $query;
				}
			}
		}
		$this->query = $query;
		
		
		foreach($this->queries as $sbas=>$qs)
		{
			if($sbas !== 'main')
			{
				if(count($this->options['status']) > 0)
				{
					$requestStat = 'xxxx';
					
					for($i=4;($i<=64);$i++)
					{
						if(isset($this->options['status'][$i]))
						{
							$set = false;
							$val = '';
							if(isset($this->options['status'][$i]['off']) && in_array($sbas,$this->options['status'][$i]['off']))
							{
								$set = true;
								$val = '0';	
							}
							if(isset($this->options['status'][$i]['on']) && in_array($sbas,$this->options['status'][$i]['on']))
							{
								if($set)
									$val = 'x';
								else
									$val = '1';
							}
							$requestStat = ( $val != '' ? $val : 'x' ) . $requestStat;
						}
						else
							$requestStat = 'x' . $requestStat;
					}
					$requestStat = trim(ltrim($requestStat,'x'));
					if($requestStat !== '')
						$this->queries[$sbas] .= ' and (recordstatus='.$requestStat.')';
				}
				if(count($this->options['champs']) > 0)
				{
					$this->queries[$sbas] .= ' dans ('.implode(' ou ',$this->options['champs']).')';
				}
				if(($this->options['date']['minbound'] != '' || $this->options['date']['maxbound'] != '') && $this->options['date']['field'] != '')
				{
						if($this->options['date']['minbound'] != '')
							$this->queries[$sbas] .= ' AND ( ' . implode(' >= '.trim( $this->options['date']['minbound']).' OR  ' , $this->options['date']['field']).' >= '.trim($this->options['date']['minbound']) . ' ) ';
						if($this->options['date']['maxbound'] != '')
							$this->queries[$sbas] .= ' AND ( ' . implode(' <= '.trim( $this->options['date']['maxbound']).' OR  ' , $this->options['date']['field']).' <= '.trim($this->options['date']['maxbound']) . ' ) ';
				}
			}
		}
		
		$this->singleParse('main');
		foreach($this->queries as $sbas=>$qryBas)
			$this->singleParse($sbas);
		
		$phbase = phrasea::bases();
		foreach($ph_session["bases"] as $phbase)
		{
			if(!isset($this->queries[$phbase['sbas_id']]))
				continue;
				
			$sbas_id = $phbase["sbas_id"];
			$this->colls[$sbas_id] = array();
			foreach($phbase["collections"] as $coll)
			{
				if(in_array($coll["base_id"],$this->options['bases']))
					$this->colls[$sbas_id][] = (int)$coll["base_id"];	// le tableau de colls doit contenir des int
			}
			if(sizeof($this->colls[$sbas_id]) > 0)	// au - une coll de la base ?tait coch?e
			{
				if($this->needthesaurus[$sbas_id])
				{
					$domthesaurus = databox::get_dom_thesaurus($sbas_id);

					if($domthesaurus)
					{
						$this->qp[$sbas_id]->thesaurus2($this->indep_treeq[$sbas_id], $sbas_id, $phbase['dbname'], $domthesaurus, true);
						$this->qp['main']->thesaurus2($this->indep_treeq['main'], $sbas_id, $phbase['dbname'], $domthesaurus, true);
					}
				}
				
				if($this->qp[$sbas_id]->errmsg != "")
				{
					exit($this->qp[$sbas_id]->errmsg);
				}
				$emptyw = false;
					
					
				$this->qp[$sbas_id]->set_default($this->indep_treeq[$sbas_id], $emptyw);
				$this->qp[$sbas_id]->distrib_in($this->indep_treeq[$sbas_id]);
				$this->qp[$sbas_id]->factor_or($this->indep_treeq[$sbas_id]);
				$this->qp[$sbas_id]->setNumValue($this->indep_treeq[$sbas_id],$phbase["xmlstruct"]);
				$this->qp[$sbas_id]->thesaurus2_apply($this->indep_treeq[$sbas_id], $sbas_id);
				$this->arrayq[$sbas_id] = $this->qp[$sbas_id]->makequery($this->indep_treeq[$sbas_id]);
				$this->results[$sbas_id] = NULL;
			}
		}
	}
	
	
	
	
	
	
	
	

	private function explain()
	{
		$session = session::getInstance();
		$ret = '';
		foreach($this->queries as $sbas_id=>$base)
		{
			$ret .= '<div>';
			$ret .= '<h2 style="margin:10px 0 0 0;padding:0;">'.phrasea::sbas_names($sbas_id).'</h2>';
			$ret .= "<div>".$base." :  <span style='font-weight:normal;font-size:11px;'>".$this->results[$sbas_id]['time_all']." s</span></div>";
			$ret .= self::astable($this->results[$sbas_id]);
			$ret .= "<hr/>";
			$ret .= '</div>';
		}
		
		$explain = "<div id=\"explainResults\" class=\"myexplain\">" ;
//		foreach($this->queries as $q)
//			$explain.="<div>".$q."</div>";
		
		$explain .= "<img src=\"/skins/icons/answers.gif\" /><span><b>".sprintf(_('reponses:: %d Resultats'),$session->prod['query']['nba'])." </b></span>";
		$explain .= "<br>" . $ret . "" ;
		$explain .= "</div>";
		
		return $explain;
	}
	private function astable(&$tree)
	{
		self::factorNear0($tree);
		$maxdepth = 0;
		self::calc_complexity($tree, $maxdepth);
		$txt = "<table class=\"explain3\"><tr>" . self::astable2($tree, $maxdepth) . "</tr></table>";
		return($txt);
	}
	private function factorNear0(&$tree)
	{
		if($tree)
		{
			if(isset($tree["lbranch"]))
				self::factorNear0($tree["lbranch"]);
			if(isset($tree["rbranch"]))
				self::factorNear0($tree["rbranch"]);
			if(isset($tree["lbranch"]) && isset($tree["rbranch"]) && $tree["type"]==PHRASEA_OP_BEFORE && $tree["prox"]==0
					&& $tree["lbranch"]["type"]==PHRASEA_KEYLIST && $tree["rbranch"]["type"]==PHRASEA_KEYLIST
					&& is_string($tree["lbranch"]["keyword"]) && is_string($tree["rbranch"]["keyword"]) )
			{
				$tree["type"] = PHRASEA_KEYLIST;
				$tree["keyword"] = $tree["lbranch"]["keyword"] . " " . $tree["rbranch"]["keyword"];
				unset($tree["lbranch"]);
				unset($tree["rbranch"]);
			}
		}
	}
	private function calc_complexity(&$tree, &$maxdepth, $depth=0)
	{
		if($depth > $maxdepth)
			$maxdepth = $depth;
		if($tree)
		{
			if(isset($tree["lbranch"]) || isset($tree["rbranch"]))
				return($tree["COMPLEXITY"] = self::calc_complexity($tree["lbranch"], $maxdepth, $depth+1) + self::calc_complexity($tree["rbranch"], $maxdepth, $depth+1));
			else
				return($tree["COMPLEXITY"] = 1);
		}
		else
			return(0);
	}
	private function astable2(&$tree, $maxdepth, $depth=0)
	{
		$w = $ret = "";
		switch($tree["type"])
		{
			case PHRASEA_OP_OR :
				$w .= _('phraseanet::technique:: or');
				break;
			case PHRASEA_OP_AND :
				$w .= _('phraseanet::technique:: and');
				break;
			case PHRASEA_KW_ALL :
				$w .= _('phraseanet::technique:: all');
				break;
			case PHRASEA_KW_LAST :
				$w .= _('phraseanet::technique:: last');
				break;
			case PHRASEA_OP_EXCEPT :
				$w .= _('phraseanet::technique:: except');
				break;
			case PHRASEA_OP_NEAR :
				$w .= _('phraseanet::technique:: near'). "[".$tree["prox"]."]";
				break;
			case PHRASEA_OP_BEFORE :
				$w .= _('phraseanet::technique:: before') . "[".$tree["prox"]."]";
				break;
			case PHRASEA_OP_AFTER :
				$w .= _('phraseanet::technique:: after') . "[".$tree["prox"]."]";
				break;
			case PHRASEA_OP_IN :
				if(isset($tree["keyword"]) && isset($tree["field"]))
					$w = $tree["keyword"]." "._('phraseanet::technique:: in') . " " . $tree["field"];
				else
					$w = _('phraseanet::technique:: in');
				break;
			case PHRASEA_OP_COLON :
				$w = $tree["field"]." : ".$tree["value"];
				break;
			case PHRASEA_OP_EQUAL :
				$w = $tree["field"]." = ".$tree["value"];
				break;
			case PHRASEA_OP_NOTEQU :
				$w = $tree["field"]." <> ".$tree["value"];
				break;
			case PHRASEA_OP_GT :
				$w = $tree["field"]." > ".$tree["value"];
				break;
			case PHRASEA_OP_LT :
				$w = $tree["field"]." < ".$tree["value"];
				break;
			case PHRASEA_OP_GEQT :
				$w = $tree["field"]." >= ".$tree["value"];
				break;
			case PHRASEA_OP_LEQT :
				$w = $tree["field"]." <= ".$tree["value"];
				break;
			case PHRASEA_KEYLIST :
				if(is_array($tree["keyword"]))
				{
					for($cmpt=0;$cmpt<sizeof($tree["keyword"]);$cmpt++)
					{
						if($cmpt>0)
							$w .= " OR ";
						$w .=$tree["keyword"][$cmpt];
					}
				}
				else
					$w = $tree["keyword"];
				break;
			default :
				$w = $tree["type"];
				break;
		}
		$ret .= "<td";
		if($tree["COMPLEXITY"]>1)
			$ret .= (" rowspan=\"".$tree["COMPLEXITY"]."\"");
		if($tree["type"]==PHRASEA_KEYLIST && ($maxdepth+1-$depth>1))
			$ret .= (" colspan=\"" . ($maxdepth+1-$depth) ."\"");
		$ret .= ">" . $w ;
		$ret .= "&nbsp;<i>(". $tree["nbanswers"].")</i></td>";
	
		if(isset($tree["lbranch"]))
		{
			$ret .= self::astable2( $tree["lbranch"], $maxdepth, $depth+1);
			$ret .= "</tr><tr>";
		}
		if(isset($tree["rbranch"]))
			$ret .= self::astable2( $tree["rbranch"], $maxdepth, $depth+1);
			
		return $ret;
	}
	
	
	
	
	
	
	
}