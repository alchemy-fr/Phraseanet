<?php
class answer
{
	
	public static function renew_token($base_id,$record_id)
	{
		$preview = self::get_preview($base_id,$record_id, false);

		$ret = false;
		
		if(isset($preview['flashcontent']))
			$ret = $preview['flashcontent']['flv'];
		return $ret;
	}
	
	public static function get_preview_rollover($base_id,$record_id,$ses,$canprev,$usr,$sd,$docType)
	{
		
		$cache_preview = cache_preview::getInstance();
		
		$sbas_id = phrasea::sbasFromBas($base_id);
		
		$isVideo = $isAudio = $isImage = $isDocument = false;
	
		$isVideo = $docType == 'video' ? true:false;
		$isAudio = $docType == 'audio' ? true:false;
		$isImage = $docType == 'image' ? true:false;
		$isFlash = $docType == 'flash' ? true:false;
		$isDocument = $docType == 'document' ? true:false;
		
		if((!$isVideo || !GV_h264_streaming) && ($tmp = $cache_preview->get($sbas_id,$record_id,$canprev)) !== false)
			return $tmp;
		$session = session::getInstance();
	
		$JS_roll ="";
		$url_ext = '';
		if($isImage || $isFlash)
		{
			if(isset($sd['preview']['credate']) && isset($sd['preview']['moddate']))
			{
				if($sd['preview']['credate'] != $sd['preview']['moddate'])
				{
					$modtime = new DateTime($sd['preview']['moddate']);
					$nowtime = new DateTime('-4 days');
					if($modtime>$nowtime)
						$url_ext = '&'.mt_rand();
				}
			}	
			if(isset($sd["preview"]["width"]) && $canprev && file_exists(p4string::addEndSlash($sd['preview']['path']).$sd['preview']['file']))
			{
				$prev = "/include/directprev.php?bas=".$base_id."&rec=".$record_id.$url_ext;							
				$JS_roll = "<img class=\"imgTips\" src=\"".$prev."\" border=\"0\" style=\"z-index:99; xvisibility:hidden;width:".$sd["preview"]["width"]."px;height:".$sd["preview"]["height"]."px\">";		
			}
			elseif(isset($sd["thumbnail"]) && file_exists(p4string::addEndSlash($sd['thumbnail']['path']).$sd['thumbnail']['file']))
			{
				$sd["preview"] = $sd["thumbnail"];
				$prev = '/'.p4string::addEndSlash($sd["thumbnail"]["baseurl"]).$sd["thumbnail"]["file"];
				$JS_roll = "<img class=\"imgTips\" src=\"".$prev."\" style=\"z-index:99; width:".round($sd["preview"]["width"]*1)."px;height:".round($sd["preview"]["height"]*1)."px\">";		
			}
		}
		elseif($isVideo)
		{
			if(isset($sd["preview"]["width"]) && $canprev)
			{
				
				$fileName = p4string::addEndSlash($sd["preview"]['path']).$sd["preview"]['file'];
				
				$preview = p4file::apache_tokenize($fileName);

				if(!$preview)
					$preview = '/include/directprev.php%3F'."bas%3D".$base_id."%26rec%3D".$record_id;
						
				$JS_roll = '<div class="imgTips" style="z-index:99;width:'.((int)$sd["preview"]["width"]+10).'px;height:'.((int)$sd["preview"]["height"]+10).'px" id="rolloverpreview"></div>
		<script type="text/javascript">flowplayer("rolloverpreview", {src:"/include/flowplayer/flowplayer-3.2.2.swf", wmode: "transparent"}, {clip:{url:"'.$preview.'",autoPlay: true,autoBuffering:true,provider: "h264streaming",scaling:"fit"}, onError:function(code,message){getNewVideoToken('.$base_id.', '.$record_id.', this);},plugins: {h264streaming: {url: "/include/flowplayer/flowplayer.pseudostreaming-3.2.2.swf"}}});</script>';

			}
		}
		elseif($isDocument)
		{
			if(isset($sd["preview"]["width"]) && $canprev)
			{
				$token = md5(time().mt_rand(100000,999999));
				$width = $height = 500;
				$preview = '/include/directprev.php%3F'."bas%3D".$base_id."%26rec%3D".$record_id;
					
				$JS_roll = '<div class="imgTips" 
								style="z-index:99;width:'.($width).'px;
										height:'.($height).'px">
										<div id="rollover'.$base_id.'_'.$record_id.'_'.$token.'"></div>
							</div>
							<script type="text/javascript">swfobject.embedSWF("/include/FlexPaper_flash/FlexPaperViewer.swf", "rollover'.$base_id.'_'.$record_id.'_'.$token.'", "100%", "100%", "9.0.0", false, false, {menu: "false",flashvars: "SwfFile='.$preview.'&Scale=0.6&ZoomTransition=easeOut&ZoomTime=0.5&ZoomInterval=0.1&FitPageOnLoad=true&FitWidthOnLoad=true&PrintEnabled=true&FullScreenAsMaxWindow=false&localeChain='.$session->locale.'",	movie: "/include/FlexPaper_flash/FlexPaperViewer.swf",	allowFullScreen :"true",wmode: "transparent"}, false);</script>';
			}
		}
		elseif($isAudio)
		{
			if(isset($sd["preview"]["width"]) && $canprev)
			{
				$prev = "/include/directprev.php%3Fbas%3D".$base_id."%26rec%3D".$record_id;
				$JS_roll = "<object class=\"audioTips\" style=\"z-index:2;left:0;height:24px;\" width=\"290\" height=\"24\" id=\"audioplayer1\" data=\"/include/audio-player/player.swf\" type=\"application/x-shockwave-flash\">
				<param value=\"/include/audio-player/player.swf\" name=\"movie\"/>
				<param value=\"playerID=1&autostart=yes&noinfo=yes&animation=no&remaining=yes&soundFile&soundFile=".$prev."\" name=\"FlashVars\"/>
				<param value=\"high\" name=\"quality\"/>
				<param value=\"false\" name=\"menu\"/>
				<param value=\"transparent\" name=\"wmode\"/>
			</object>";
			}
		}
		
		$cache_preview->set($sbas_id,$record_id,$canprev,$JS_roll);
		
		
		return $JS_roll;
		
	}
	
	public static function get_preview($base_id, $record_id, $isFullyPublic)
	{
		$session = session::getInstance();
		
		$flashcontent = $preview = $url = false;
		$width = $height = $html_view = false;
		$sdMain = phrasea_subdefs($session->ses_id,$base_id,$record_id);
	
		$user = user::getInstance($session->usr_id);
		
		$doctype = 'unknown';
		$typedoc = null;
		
		if(isset($sdMain["preview"]) && $sdMain["preview"])
		{
			$key = $base_id.'_'.$record_id;
			if(($isFullyPublic) 
				|| (isset($user->_rights_bas[$base_id]) && $user->_rights_bas[$base_id]['canpreview'] == true) 
				|| array_key_exists($key, $user->_rights_records))
			{
				$typedoc = 'preview';
			}
			elseif(isset($sdMain['thumbnail']))
			{
				$typedoc = 'thumbnail';
			}
		}
			$needSubstit = false;
//		if($typedoc == null && isset($canHD[$bas]) && $canHD[$bas] && isset($sdMain[$typedoc]))
//		{
//				$typedoc = 'document';
//				if(!in_array($sdMain[$typedoc]['mime'],array('image/jpeg','image/gif','video/x-flv','audio/x-wav','audio/wav','audio/mpeg')))
//					$needSubstit = true;
//					
//		}
		if($typedoc == null && isset($sdMain['thumbnail']))
		{
			$typedoc = 'thumbnail';
		}
		
		$gviewer_docs = array(
			'application/vnd.oasis.opendocument.text',
			'application/pdf',
			'application/vnd.oasis.opendocument.presentation',
			'application/vnd.oasis.opendocument.speadsheet',
			'application/msword',
			'application/mspowerpoint',
			'application/x-shockwave-flash',
			'application/msexcel',
			'application/vnd.ms-powerpoint'
		);
		
		if($typedoc == null && isset($sdMain['document']))
		{
//			if(!in_array($sdMain['document']['mime'],$gviewer_docs))
			$needSubstit = true;
			$typedoc = 'document';
		}
		
		if($typedoc != null && isset($sdMain[$typedoc]))
		{
			if($needSubstit || !in_array($sdMain[$typedoc]['mime'],array_merge($gviewer_docs,array('application/x-shockwave-flash','image/jpeg','image/gif','video/x-flv','video/mp4','audio/x-wav','audio/wav','audio/mpeg'))))
			{
				$url = '/skins/icons/substitution/' . str_replace('/', '_', $sdMain[$typedoc]['mime']) . '.png';
				$preview = '<img class="PREVIEW_PIC" src="'.$url.'"/>';

				$needSubstit = true;
				$width = '128';
				$height = '128';
			}
			elseif(isset($sdMain[$typedoc]["width"]) && isset($sdMain[$typedoc]["height"]))
			{
				$width = $sdMain[$typedoc]["width"];
				$height = $sdMain[$typedoc]["height"];
			}
			if(!$needSubstit)
			{
				if($sdMain[$typedoc]["baseurl"]!=null && $sdMain[$typedoc]["baseurl"]!="" )
			 	{
					$url = $preview = '/'.p4string::addEndSlash($sdMain[$typedoc]["baseurl"]).$sdMain[$typedoc]["file"]."?mt=".mt_rand();

					$doctype = 'image';
				}
				else
				{
					if(in_array($sdMain[$typedoc]['mime'],array('image/jpeg','image/gif')))
					{
						$url = $preview = "/include/directprev.php?bas=".$base_id."&rec=".$record_id;
						$doctype = 'image';
						
						if(isset($sdMain['document']['credate']) && isset($sdMain['document']['moddate']))
						{
							if($sdMain['document']['credate'] != $sdMain['document']['moddate'])
							{
								$modtime = new DateTime($sdMain['document']['moddate']);
								$nowtime = new DateTime('-4 days');
								if($modtime>$nowtime)
								{
									$url .= '&t='.mt_rand();
									$preview .= '&t='.mt_rand();
								}
							}
						}	
					}
					elseif(in_array($sdMain[$typedoc]['mime'],$gviewer_docs))
					{
						$url = $preview = '/include/directprev.php%3F'."bas%3D".$base_id."%26rec%3D".$record_id;
						$doctype = 'flash';
					}
					elseif(in_array($sdMain[$typedoc]['mime'],array('video/x-flv','video/mp4')))
					{
						
						$fileName = p4string::addEndSlash($sdMain[$typedoc]['path']).$sdMain[$typedoc]['file'];
						
						$url = $preview = p4file::apache_tokenize($fileName);
						
						if(!$preview)
							$url = $preview = '/include/directprev.php%3F'."bas%3D".$base_id."%26rec%3D".$record_id;
						
						$doctype = 'video';
					}
					elseif(in_array($sdMain[$typedoc]['mime'],array('audio/x-wav','audio/wav','audio/mpeg')))
					{
						$url = $preview = '/include/directprev.php%3F'."bas%3D".$base_id."%26rec%3D".$record_id;
						$doctype = 'audio';
					}
				}
				if(in_array($sdMain[$typedoc]['mime'],array('image/jpeg','image/gif')))
				{
					$html_view = '<img class="record record_image" style="width:'.$width.'px;height:'.$height.'px;" src="'.$preview.'">
									<input type="hidden" name="width" value="'.$width.'"/>
									<input type="hidden" name="height" value="'.$height.'"/>';
					$preview = 	'<img oncontextMenu="return(false);" class="PREVIEW_PIC zoomable" src="'.$preview.'">';
				}
				elseif(in_array($sdMain[$typedoc]['mime'],array('video/x-flv','video/mp4')))
				{
					
					$flashcontent = array("height"=>$height,"width"=>$width,'flv'=>$preview);
					
					
					$token = md5(time().mt_rand(100000,999999));
					
					$html_view = '<div class="record record_video" style="width:'.$width.'px;height:'.$height.'px;">
									<div id="preview_'.$base_id.'_'.$record_id.'_'.$token.'" class="PNB" style=""></div>
									<input type="hidden" name="width" value="'.$width.'"/>
									<input type="hidden" name="height" value="'.$height.'"/>
									</div><script type="text/javascript">flowplayer("preview_'.$base_id.'_'.$record_id.'_'.$token.'",{src:"/include/flowplayer/flowplayer-3.2.2.swf", wmode: "transparent"},{clip:{url:"'.$preview.'",autoPlay: true,autoBuffering:true,provider: "h264streaming",scaling:"fit"},onError:function(code,message){getNewVideoToken('.$base_id.', '.$record_id.', this);},plugins: {h264streaming: {url: "/include/flowplayer/flowplayer.pseudostreaming-3.2.2.swf"}}});</script>';
					
					$preview = '<div id="FLASHPREVIEW" class="PREVIEW_PIC" style="margin:0 auto;width: 600px; height: 300px;" ></div>';
//					$width = $height = '200';
				}
				elseif(in_array($sdMain[$typedoc]['mime'],$gviewer_docs))
				{
					$width = $height = '500';
					$token = md5(time().mt_rand(100000,999999));

					$flashcontent = array("height"=>$height,"width"=>$width,"url"=>'/include/FlexPaper_flash/FlexPaperViewer.swf',
						"flashVars"=>'SwfFile='.$preview.'&Scale=0.6&ZoomTransition=easeOut&ZoomTime=0.5&ZoomInterval=0.1&FitPageOnLoad=true&FitWidthOnLoad=true&PrintEnabled=true&FullScreenAsMaxWindow=false&localeChain='.$session->locale);
					
					$html_view = '<div class="record record_document" style="width:'.$width.'px;height:'.$height.'px;">
									<div id="preview_'.$base_id.'_'.$record_id.'_'.$token.'" class="PNB" style=""></div>
									<input type="hidden" name="width" value="'.$width.'"/>
									<input type="hidden" name="height" value="'.$height.'"/>
									</div><script type="text/javascript">swfobject.embedSWF("/include/FlexPaper_flash/FlexPaperViewer.swf", "preview_'.$base_id.'_'.$record_id.'_'.$token.'", "100%", "100%", "9.0.0", false, false, {menu: "false",flashvars: "SwfFile='.$preview.'&Scale=0.6&ZoomTransition=easeOut&ZoomTime=0.5&ZoomInterval=0.1&FitPageOnLoad=true&FitWidthOnLoad=true&PrintEnabled=true&FullScreenAsMaxWindow=false&localeChain='.$session->locale.'",	movie: "/include/FlexPaper_flash/FlexPaperViewer.swf",	allowFullScreen :"true",wmode: "transparent"}, false);</script>';
									
					$preview = '<object class="PREVIEW_PIC" type="application/x-shockwave-flash" data="">' .
						'<param name="movie" value="/include/FlexPaperViewer.swf" />' .
						'<param name="allowFullScreen" value="true" />' .
						'<param name="wmode" value="transparent" />' .
						'<param name="FlashVars" value="" />' .
						'</object>';
					$preview = '<div id="FLASHPREVIEW" class="PREVIEW_PIC"></div>';
				}
				elseif(in_array($sdMain[$typedoc]['mime'],array('audio/x-wav','audio/wav','audio/mpeg')))
				{
					$preimage = '';
					$token = md5(time().mt_rand(100000,999999));
					$width = $sdMain['thumbnail']["width"];
					$height = $sdMain['thumbnail']["height"];
					if($typedoc != 'thumbnail' && isset($sdMain['thumbnail']) && isset($sdMain['thumbnail']['width']) && $sdMain['thumbnail']["file"] != 'audio.jpg')
					{
						if($sdMain['thumbnail']["baseurl"]!=null && $sdMain['thumbnail']["baseurl"]!="" )
							$preurl = 'include/'.p4string::addEndSlash($sdMain['thumbnail']["baseurl"]).$sdMain['thumbnail']["file"]."?mt=".mt_rand();
						if($preurl != '')
							$preimage = '<div><img src="'.$preurl.'" onload="setVisible(this)" class="PREVIEW_PIC" style="width:'.$sdMain['thumbnail']['width'].'px;height:'.$sdMain['thumbnail']['height'].'px;" ></div>';
					}
					$flashcontent = array('height'=>26,"width"=>290,"url"=>"/include/audio-player/player.swf",
					"flashVars"=>"playerID=2&autostart=yes&noinfo=yes&animation=no&remaining=yes&soundFile=".$preview);
					
					$html_view = '<div class="record record_audio" style="width:290px;height:26px;">
									<div id="preview_'.$base_id.'_'.$record_id.'_'.$token.'" class="PNB" style=""></div>
									<input type="hidden" name="width" value="290"/>
									<input type="hidden" name="height" value="26"/>
									</div><script type="text/javascript">swfobject.embedSWF("/include/audio-player/player.swf", "preview_'.$base_id.'_'.$record_id.'_'.$token.'", "290", "26", "9.0.0", false, false, {menu: "false",flashvars: "playerID=2&autostart=yes&noinfo=yes&animation=no&remaining=yes&soundFile=/'.$preview.'", movie: "/include/audio-player/player.swf",	allowFullScreen :"true",wmode: "transparent"}, false);</script>';
									
					$preview = '<div style="margin:20px 0;position:relative;top:20px;height:24px;text-align:center;">
						<div id="FLASHPREVIEW" class="PREVIEW_PIC" ></div>
						</div>'.$preimage.'';
				}
			}
	
	
		}
		
		return array(
			'preview' 		=> $preview,
			'flashcontent'	=> $flashcontent,
			'width'			=> $width,
			'height'		=> $height,
			'doctype'		=> $doctype,
			'html'			=> $html_view,
			'url'			=> $url
		);
	}
	
	public static function get_duration($xml)
	{
		$duration = 0;
		
		if($infoXml = simplexml_load_string($xml))
		{
			foreach($infoXml->doc->Attributes() as $k=>$v)
			{
				if($k == 'duration')
					$duration = (int)$v;
			}
		}
		return self::format_duration($duration);
	}
	
	public static function format_duration($d)
	{
		$durations = $durationm = $durationh = 0;
		$durations = fmod($d,60);
		$durations = $durations<=9?'0'.$durations:$durations;
		$durationm = fmod(floor($d/60),60);
		$durationm = ($durationm<=9?'0'.$durationm:$durationm).':';
		$durationh = floor($d/3600);
		$durationh = $durationh==0?'':(($durationh<=9?'0'.$durationh:$durationh).':');
		$d = $durationh.$durationm.$durations;
		return $d;
	}

	public static function format_infos($xml,$sbas_id,$record_id,$docType)
	{
		if(trim($xml) == '')
			return '';
			
		$session = session::getInstance();
			
		$locale = $session->locale;
		
		$cache_record = cache_record::getInstance();
		
		if(($tmp = $cache_record->get($sbas_id,$record_id,'infos_'.$locale)) != false)
			return $tmp;
		
		$exifinfos = '';
		$xml = preg_replace(array("/>\s*/", "/\n/"), array(">", "[[br/]]"), $xml);
		$dom_doc = new DOMDocument();
		
		$basesettings = phrasea::load_settings($locale);
		
		if($dom_doc->loadXML($xml))
		{
			if($basesettings["xsltinfo"] != '')
			{
				$dom_doc_xsl = new DOMDocument;
				$dom_doc_xsl->loadXML($basesettings["xsltinfo"]);
				
				$xslt = new XSLTProcessor();
				$xslt->importStylesheet($dom_doc_xsl);
				
				$xslt->setParameter('', 'record_id', $record_id);
				switch($docType)
				{
					case 'video':
						$duration = answer::get_duration($xml);
						$xslt->setParameter('', 'duration', $duration);
						break;
					case 'audio':
					$duration = answer::get_duration($xml);
					$xslt->setParameter('', 'duration', $duration);
						break;
				}
						
				$xslt->setParameter('', 'docutype', $docType);
					
				$exifinfos = trim($xslt->transformToXML($dom_doc));
			
				$exifinfos = str_replace(array("\r\n","\r","\n","  "),array("","",""," "),$exifinfos);
			}
		}
				
		$cache_record->set($sbas_id,$record_id,'infos_'.$locale,$exifinfos);		
		
		return $exifinfos;		
	}
	
	function splitTermAndContext($word)
	{
		$term = trim($word);
		$context = "";
		if(($po = strpos($term, "(")) !== false)
		{
			if(($pc = strpos($term, ")", $po)) !== false)
			{
				$context = trim(substr($term, $po+1, $pc-$po-1));
				$term = trim(substr($term, 0, $po));
			}
			
		}
		return(array($term, $context));
	}
		
	public static function format_caption($base_id, $record_id, $xml, $with_bounce = false, $template=false)
	{
		$session = session::getInstance();
		
		if(trim($xml) == '')
			return _('reponses::record::Pas de description');
		
		$locale = $session->locale;
		require_once( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
		$cache_record = cache_record::getInstance();
		
		$sbas_id = phrasea::sbasFromBas($base_id);
		
		$md5 = md5($xml);
		
		$cache_key = 'caption'.$md5.'_'.$locale.'_'.($with_bounce ? '1' : '0').'_'.$template;
		
		if(($tmp = $cache_record->get($sbas_id,$record_id,$cache_key)) != false)
			return $tmp;

		$xml = preg_replace(array("/>\s*/", "/\n/"), array(">", "[[br/]]"), $xml);
		$dom_doc = new DOMDocument();
		
		$basesettings = phrasea::load_settings($locale);
		
	
		$captions = _('reponses::record::Pas de description');
		
		$xsl = '';
		if(isset($basesettings["colls"][$base_id]))
		{
			if(function_exists("phrasea_isgrp") && isset($session->ses_id) && phrasea_isgrp($session->ses_id, $base_id, $record_id) != false)
				$xsl = $basesettings["colls"][$base_id]["xsltRollOverGrp"];
			elseif($template == 'homelink' && isset($basesettings["colls"][$base_id]["xslthomelink"]))
				$xsl = $basesettings["colls"][$base_id]["xslthomelink"];
			else
				$xsl = $basesettings["colls"][$base_id]["xsltRollOver"];
		}
		
		if($dom_doc->loadXML($xml))
		{
			$XPATH_thesaurus = databox::get_xpath_thesaurus($sbas_id);
			
			$XPATH_struct = databox::get_xpath_structure($sbas_id);
			$DOM_thFields = array();
			
			if($XPATH_struct)
				$DOM_thFields = $XPATH_struct->query("/record/description/*[@tbranch!='']");

			$XPATH_caption = new DOMXPath( $dom_doc );
			foreach($DOM_thFields as $DOM_thField)
			{
				$tbranch = $DOM_thField->getAttribute("tbranch");
				$DOM_branchs = $XPATH_thesaurus->query($tbranch);
				
				$DOM_fields = $XPATH_caption->query("/record/description/" . $DOM_thField->nodeName);
				foreach($DOM_fields as $DOM_field)
				{
					$fvalue = $DOM_field->nodeValue;
					
					// le terme n'est cliquable que s'il est dans le thesaurus
					$cleanvalue = str_replace(array("<em>", "</em>", "'"), array("", "", "&apos;"), $fvalue);
					
					list($term_noacc, $context_noacc) = self::splitTermAndContext($cleanvalue);
					$term_noacc    =  noaccent_utf8($term_noacc, PARSED);
					$context_noacc =  noaccent_utf8($context_noacc, PARSED);
					if($context_noacc)
					{
						$q = "($tbranch)//sy[@w='".$term_noacc."' and @k='".$context_noacc."']";
					}
					else
					{
						$q = "($tbranch)//sy[@w='".$term_noacc."' and not(@k)]";
					}
					$t = "";
					foreach($DOM_branchs as $DOM_branch)
					{
						$nodes = $XPATH_thesaurus->query($q, $DOM_branch);
						if($nodes->length > 0)
						{
							$lngfound = false;
							foreach($nodes as $node)
							{
								if($node->getAttribute("lng") == $session->usr_i18n)
								{
									// le terme est dans la bonne langue, on le rend cliquable
									list($term, $context) = self::splitTermAndContext($fvalue);
									$term    = str_replace(array("<em>", "</em>"), array("", ""), $term);
									$context = str_replace(array("<em>", "</em>"), array("", ""), $context);
									$qjs = $term;
									if($context)
									{
										$qjs  .= " [".$context."]";
									}
									$t  = "[[a class=\"bounce\" onclick=\"bounce('".$sbas_id."','";
									$t .= p4string::MakeString($qjs, "js");
									$t .= "', '";
									$t .= p4string::MakeString($DOM_field->nodeName, "js");
									$t .= "');return(false);\"]]";
									$t .= $fvalue;
									$t .= "[[/a]]";
									
									$lngfound = true;
									break;
								}
								
								$synonyms = $XPATH_thesaurus->query("sy[@lng='" . $session->usr_i18 . "']", $node->parentNode);
								foreach($synonyms as $synonym)
								{
									$k = $synonym->getAttribute("k");
									if($synonym->getAttribute("w") != $term_noacc || $k != $context_noacc)
									{
										$link = $qjs = $synonym->getAttribute("v");
										if($k)
										{
											$link .= " (".$k.")";
											$qjs  .= " [".$k."]";
										}
		
										$t  = "[[a class=\"bounce\" onclick=\"bounce('";
										$t .= p4string::MakeString($qjs, "js");
										$t .= "', '";
										$t .= p4string::MakeString($DOM_field->nodeName, "js");
										$t .= "');return(false);\"]]";
										$t .= $link;
										$t .= "[[/a]]";
										
										$lngfound = true;
										break;
									}
								}
							}
							if(!$lngfound)
							{
								list($term, $context) = self::splitTermAndContext($fvalue);
								$term    = str_replace(array("<em>", "</em>"), array("", ""), $term);
								$context = str_replace(array("<em>", "</em>"), array("", ""), $context);
								$qjs = $term;
								if($context)
								{
									$qjs  .= " [".$context."]";
								}
								$t  = "[[a class=\"bounce\" onclick=\"bounce('".$sbas_id."','";
								$t .= p4string::MakeString($qjs, "js");
								$t .= "', '";
								$t .= trim(p4string::MakeString($DOM_field->nodeName, "js"));
								$t .= "');return(false);\"]]";
								$t .= $fvalue;
								$t .= "[[/a]]";
							}
						}
					}
					if($t)	
						$DOM_field->nodeValue =  htmlspecialchars($t); 
				}
			}
			
			if($xsl != '')
			{
				$xslt_proc = new XSLTProcessor();
				
				$dom_doc_roll = new DOMDocument();
				$dom_doc_roll->loadXML($xsl);
				
				$xslt_proc->importStylesheet($dom_doc_roll);
				
				$rollDesc = $xslt_proc->transformToXML($dom_doc);
	
				if($rollDesc != "")
				{
					$captions = $rollDesc;
				}
			}
		}
		
//		preg_match_all("((https?|ftp|gopher|file):((//)|(\\\\))[\w\d:#%/;$()~_?\-=\\\.&]*)",$captions,$matches);
//
//		$urls = array_unique($matches[0]);
//var_dump($urls);
//		foreach($urls as $url)
//		{
//			$captions = str_replace($url,'<a href="'.$url.'" target="_blank">'.$url.'</a>',$captions);
//		}

		
		$captions = (p4string::entitydecode($captions));
		$captions = preg_replace("(([^']{1})((https?|file):((/{2,4})|(\\{2,4}))[\w:#%/;$()~_?/\-=\\\.&]*)([^']{1}))",'$1 $2 <a title="'._('Open the URL in a new window').'" class="ui-icon ui-icon-extlink" href="$2" style="display:inline;padding:2px 5px;margin:0 4px 0 2px;" target="_blank"> &nbsp;</a>$7',$captions);
//		var_dump($captions);
		$cache_record->set($sbas_id,$record_id,$cache_key,$captions);		
		
		return $captions;
	}
	
	public static function format_title($sbas_id, $record_id, $xml)
	{
		if(trim($xml) == '')
			return _('reponses::document sans titre');
			
		$session = session::getInstance();
			
		$cache_record = cache_record::getInstance();
		$locale = $session->locale;
		$md5 = md5($xml);
		
		if(($tmp = $cache_record->get($sbas_id,$record_id,'title'.$md5.'_'.$locale)) != false)
			return $tmp;
		
		$xml = preg_replace(array("/>\s*/", "/\n/"), array(">", "[[br/]]"), $xml);
		$dom_doc = new DOMDocument();
		
		$basesettings = phrasea::load_settings($locale);
		
		$title = '';
		if($dom_doc->loadXML($xml))
		{
			if($basesettings["bases"][$sbas_id]["xsl_title"])
			{
				
				$xslt_proc = new XsltProcessor();
				$dom_doc_title = new DOMDocument();
				
				if($dom_doc_title->loadXML($basesettings["bases"][$sbas_id]["xsl_title"]))
				{
				
					$xslt_proc->importStylesheet( $dom_doc_title );
					$xslt_proc->setParameter(null, "field", "Titre");
					
					$title = trim($xslt_proc->transformToXML($dom_doc));

					if($basesettings["bases"][$sbas_id]["defaultxml"])
					{
						$title = basename($title);
					}

					$l = mb_strlen($title);
					if($l>120)
						$title = p4string::cutDesc($title,120,"[[em]]","[[/em]]");
				}
			}
		}
		
		
		$title = p4string::entitydecode($title != "" ? $title : "<i>"._('reponses::document sans titre')."</i>");
		
		$cache_record->set($sbas_id,$record_id,'title'.$md5.'_'.$locale,$title);
		
		return $title;
	}
	
	public static function getThumbnail($ses, $bid, $rid,$getPrev=false)
	{
		static $substitutionfiles;
		
		$sbas_id = phrasea::sbasFromBas($bid);
		
		$cache_thumb = cache_thumbnail::getInstance();
		
		if(($tmp = $cache_thumb->get($sbas_id, $rid, $getPrev)))
		{
			return $tmp;
		}
		$w = $h = $rollover_width = $rollover_height = 64;
		$sd = phrasea_subdefs($ses, $bid, $rid);
	
		$thumbnail = $rollover = null;
		$find = $sha = FALSE ;
		$mime = $extcur = '';
		$docType = 'unknown';
		$bitly = null;
		$url_ext = '';
		$deleted = false;
		$orientation = 'portrait';
		
		if($sd)
		{
			if(isset($sd['thumbnail']) && $sd['thumbnail'])
			{
				$thumbnail = '/'.p4string::addEndSlash($sd['thumbnail']['baseurl']).$sd['thumbnail']['file'];
	
				$w = $sd['thumbnail']['width'];
				$h = $sd['thumbnail']['height'];
				$imgclass = ($sd['thumbnail']['width'] > $sd['thumbnail']['height']) ? 'hthbimg' : 'vthbimg';
				$orientation = ($sd['thumbnail']['width'] > $sd['thumbnail']['height']) ? 'landscape' : 'portrait';
				
				$bitly = $sd['thumbnail']['bitly'];
	
				if( file_exists($sd['thumbnail']['path'].$sd['thumbnail']['file']) )
					$find = TRUE ;
				else
					$thumbnail = null;
			}
			if(isset($sd['thumbnailGIF']) && $sd['thumbnailGIF'])
			{
				if( file_exists($sd['thumbnailGIF']['path'].$sd['thumbnailGIF']['file']) )
				{
					$rollover = '/'.p4string::addEndSlash($sd['thumbnailGIF']['baseurl']).$sd['thumbnailGIF']['file'];
					$rollover_width = $sd['thumbnailGIF']['width'];
					$rollover_height = $sd['thumbnailGIF']['height'];
				}
			}
			if(isset($sd['document']))
			{
				if(isset($sd['document']['file']))
				{
					$mime = isset($sd['document']['mime'])?$sd['document']['mime']:'application/octet-stream';
					$extcur 	= pathinfo($sd["document"]["file"]);
					$extcur 	= isset($extcur["extension"])?$extcur["extension"]:'';	
					$sha 	= isset($sd['document']['sha256'])?$sd['document']['sha256']:false;
					$bitly = $sd['document']['bitly'];
				}
				if(isset($sd['document']['credate']) && isset($sd['document']['moddate']))
				{
					if($sd['document']['credate'] != $sd['document']['moddate'])
					{
						$modtime = new DateTime($sd['document']['moddate']);
						$nowtime = new DateTime('-4 days');
						if($modtime>$nowtime)
							$url_ext = '?'.mt_rand();
					}
				}	
			}
			if(isset($sd['document']['type']))
				$docType = $sd['document']['type'];
			if(!$find)
			{
	
				// pas de thmbnail : substitution selon mime
				if(isset($sd['document']) && $sd['document'])
				{
					if(isset($sd['document']['mime']))
						$mime = str_replace('/', '_', $sd['document']['mime']);
					else
						$mime = 'application_octet-stream';
					$mime = trim($mime)!=''?$mime:'application_octet-stream';
					// on verifie que l'image de substitution est connue
					if(!isset($substitutionfiles[$mime]))
					{
						// non : on la cherche
						$thumbnail = '/skins/icons/substitution/' . $mime . '.png';
						$thumbnail = str_replace('+', '%20', $thumbnail);
						
						if(file_exists(GV_RootPath . 'www/' . $thumbnail) )
						{
							$substitutionfiles[$mime] = $thumbnail;
						}
						else 
						{
							$substitutionfiles[$mime] = '/skins/icons/substitution.png';
						}
					}
					$w = $h = 256;
					$thumbnail = $substitutionfiles[$mime];
					$imgclass = 'vthbimg';
					$orientation = 'portrait';
				}
			}
		}
		if(!$thumbnail)	// pas de subdefs du tout
		{
			$thumbnail = '/skins/icons/deleted.png';
			$imgclass = 'vthbimg';
			$w = '128';
			$h = '128';
			$deleted = true;
		}
		
		$ret = array(
			'rollover'=>$rollover,
			'rollover_width'=>$rollover_width,
			'rollover_height'=>$rollover_height,
			'thumbnail'=>$thumbnail.$url_ext, 
			'deleted'=>$deleted ,
			'imgclass'=>$imgclass, 
			'orientation'=>$orientation, 
			'w'=>$w, 
			'h'=>$h, 
			'mime'=>$mime, 
			'extension'=>$extcur, 
			'type'=>$docType, 
			'bitly'=>$bitly, 
			'sha256'=>$sha
		);
		
		if($getPrev)
			$ret['preview'] = $sd;
		
		$cache_thumb->set($sbas_id, $rid, $getPrev,$ret);
			
		return $ret;
	}
	
	public static function getXslRollOver2($preff , $name = '' , $skinsLNG = '',$prev = false )
	{
		$priority = 0;
		#  Taux de precision du xsl trouve
		#  1 : xml sans non , sans  langue
		#  2 : xml sans nom , bonne langue
		#  3 : xml bon nom  , sans  langue
		#  4 : xml bon nom  , bonne langue
		$myxsl = null;
		$mystruct =  simplexml_load_string($preff) ;
		if($mystruct)
			foreach ($mystruct->layout as $layout)
			{
				if(strtoupper((string)$layout['doctype'])!=strtoupper("grouping") && strtoupper((string)$layout['name'])==strtoupper($name) && strtoupper((string)$layout['skin'])==strtoupper($skinsLNG))
				{
					$priority = 4;
					foreach ($layout->children("http://www.w3.org/1999/XSL/Transform") as $second_gen)
					{
						$myxsl = $second_gen->asXML();
						break;
					}
				}
				elseif(strtoupper((string)$layout['doctype'])!=strtoupper("grouping") && $priority<3 && strtoupper((string)$layout['name'])==strtoupper($name) && (string)$layout['skin']=='')
				{
					$priority=3;
					foreach ($layout->children("http://www.w3.org/1999/XSL/Transform") as $second_gen)
					{
						$myxsl = $second_gen->asXML();
					}
				}
				elseif(strtoupper((string)$layout['doctype'])!=strtoupper("grouping") &&  !$prev && $priority<2 && (string)$layout['name']=='' && strtoupper((string)$layout['skin'])==strtoupper($skinsLNG))
				{
					$priority=2;
					foreach ($layout->children("http://www.w3.org/1999/XSL/Transform") as $second_gen)
					{
						$myxsl = $second_gen->asXML();
					}
				}
				elseif(strtoupper((string)$layout['doctype'])!=strtoupper("grouping") &&  !$prev && $priority<1 && (string)$layout['name']=='' && (string)$layout['skin']=='')
				{
					$priority=1;
					foreach ($layout->children("http://www.w3.org/1999/XSL/Transform") as $second_gen)
					{
						$myxsl = $second_gen->asXML();
					}
				}
			}
		return $myxsl;
	}
	
	public static function getXslRollOver2Grp($preff , $name = '' , $skinsLNG = '',$prev = false )
	{
		
		$priority = 0;
		#  Taux de precision du xsl trouve
		#  1 : xml sans non , sans  langue
		#  2 : xml sans nom , bonne langue
		#  3 : xml bon nom  , sans  langue
		#  4 : xml bon nom  , bonne langue
		$myxsl = null;
		$mystruct =  simplexml_load_string($preff) ;
		if($mystruct)
			foreach ($mystruct->layout as $layout)
			{			
				if(strtoupper((string)$layout['doctype'])==strtoupper("grouping") && strtoupper((string)$layout['name'])==strtoupper($name) && strtoupper((string)$layout['skin'])==strtoupper($skinsLNG))
				{
					$priority = 4;
					foreach ($layout->children("http://www.w3.org/1999/XSL/Transform") as $second_gen)
					{
						$myxsl = $second_gen->asXML();
						break;
					}
				}
				elseif(strtoupper((string)$layout['doctype'])==strtoupper("grouping") && $priority<3 && strtoupper((string)$layout['name'])==strtoupper($name) && (string)$layout['skin']=='')
				{
					$priority=3;
					foreach ($layout->children("http://www.w3.org/1999/XSL/Transform") as $second_gen)
					{
						$myxsl = $second_gen->asXML();
					}
				}
				elseif(strtoupper((string)$layout['doctype'])==strtoupper("grouping") &&  !$prev && $priority<2 && (string)$layout['name']=='' && strtoupper((string)$layout['skin'])==strtoupper($skinsLNG))
				{
					$priority=2;
					foreach ($layout->children("http://www.w3.org/1999/XSL/Transform") as $second_gen)
					{
						$myxsl = $second_gen->asXML();
					}
				}
				elseif(strtoupper((string)$layout['doctype'])==strtoupper("grouping") &&  !$prev && $priority<1 && (string)$layout['name']=='' && (string)$layout['skin']=='')
				{
					$priority=1;
					foreach ($layout->children("http://www.w3.org/1999/XSL/Transform") as $second_gen)
					{
						$myxsl = $second_gen->asXML();
					}
				}
			}
		return $myxsl;
	}
	
	public static function getOriginalName(&$unXml)
	{		
		$originalname="";
		if($sxe = simplexml_load_string($unXml))
		{
			$z = $sxe->xpath('/record/doc');
			if($z && is_array($z))
				foreach($z[0]->attributes() as $a => $b) 
					if($a=="originalname")
						$originalname = basename((string)$b);
		}
		$before = array("\\","/","*",":","?","<",">","|","\"");
		$after =  array("_" ,"_","_","_","_","_","_","_","_" );
		$originalname = str_replace($before, $after, $originalname);
	
		return $originalname;
	}
	
	public static function getContainerBaskets($base_id, $record_id, $ssel_id)
	{
		$conn = connection::getInstance();
		$session = session::getInstance();
		$baskets = array();
		$sql = 'SELECT s.ssel_id, name, descript, c.ord FROM ssel s, sselcont c 
				WHERE s.ssel_id = c.ssel_id AND c.base_id="'.$conn->escape_string($base_id).'" 
				AND record_id="'.$conn->escape_string($record_id).'" AND usr_id="'.$conn->escape_string($session->usr_id).'" 
				AND c.ssel_id!="'.$conn->escape_string($ssel_id).'" AND temporaryType="0"';
		if($rs = $conn->query($sql))
		{
			while($row = $conn->fetch_assoc($rs))
			{
				$baskets[$row['ssel_id']] = array('name' => $row['name'], 'description' => $row['descript'], 'ord' => $row['ord']);
			}
		}
		
		return $baskets;
	}
		
	public static function correctScreenSubs($bas,$rec,$url,$prev,$w,$h,$reloadTemp)
	{
		if($url == '')
			$url = false;
	
		$out = '<script language="javascript" type="text/javascript">';
		$JS = '';
		
		if(!$prev)
		{
		
			$JS .= '' .
				'if(parent.$("#IMGT_'.$bas.'_'.$rec.'"))' .
				'{'.
				'o = parent.$("#IMGT_'.$bas.'_'.$rec.'");';
					
			if($url)
				$JS .= 'o.src = "'.$url.'";';
			
			$JS .= 'o.removeClass("hthbimg").removeClass("vthbimg").addClass("'.(($w>$h)?"hthbimg":"vthbimg").'");' .
				'}' .
				'if(parent.$("#CHIM_'.$bas.'_'.$rec.'").length>0)' .
				'{o = parent.$("#CHIM_'.$bas.'_'.$rec.'");';
					
			if($url)
				$JS .= 'o.attr("src","'.$url.'");';
			
			$JS .= 'o.width('.$w.').height('.$h.');' .
				'}';
		}
		else
		{
			$JS .= 'if(parent.$("#PREV_'.$bas.'_'.$rec.'").length>0)' .
				'{'.
				'parent.$("#PREV_'.$bas.'_'.$rec.'").attr("src","'.$url.'");' .
				'}';
		}
	
		if($reloadTemp)
		{
			$JS .= 'parent.refreshBaskets(\'current\');';	
		}
		
		$JS .= 'parent.refreshItem("'.$bas.'_'.$rec.'");';
		
		$out .= $JS.'</script>';
	
		return $out;					
	}
	
	public static function logEvent($sbas_id,$record_id,$action,$final,$comm='')
	{
		
		$session = session::getInstance();
		if(!isset($session->usr_id) || !isset($session->ses_id))
			return;
			
		$conn = connection::getInstance();
		
		$ses_id = $session->ses_id;
		$usr_id = $session->usr_id;
		
		if(!$conn)
			die();
			
		$sql = 'SELECT dist_logid FROM cache WHERE session_id="'.$conn->escape_string($ses_id).'"';
		
		$log_id = array();
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$log_id = unserialize($row['dist_logid']);
			}
			$conn->free_result($rs);
		}
		
		if(isset($log_id[$sbas_id]))
		{
			$log_id = $log_id[$sbas_id];
			
//			$sql = 'SELECT * FROM sbas WHERE sbas_id = "'.$conn->escape_string($sbas_id).'"';
//			
//			if($rs = $conn->query($sql))
//			{
//				if($row = $conn->fetch_assoc($rs))
//				{
					$connBas = connection::getInstance($sbas_id);
					if($connBas)
					{
						$sql = 'INSERT INTO log_docs (id, log_id, date, record_id, action, final, comment) VALUES (null, "'.$connBas->escape_string($log_id).'", NOW(), "'.$connBas->escape_string($record_id).'", "'.$connBas->escape_string($action).'", "'.$connBas->escape_string($final).'", "'.$connBas->escape_string($comm).'")';
						$connBas->query($sql);
					}
//				}
//				$conn->free_result($rs);
//			}
		}	
	}

	public static function writeIPTC( $sbas_id , $xml , $filesArray , $resetAllFields=false )
	{
	
		$cwd = getcwd();
		$debug = false;
		
		$arrayDesc = null;
		$mdesc = DOMDocument::loadXML( $xml );	
		$xp_mdesc = new DOMXPath( $mdesc );
		
		$recmdesc = $xp_mdesc->query("/record/description");	
		$desc = $recmdesc->item(0);
		
		for($fdesc = $desc->firstChild; $fdesc; $fdesc = $fdesc->nextSibling)
		{		
			if($fdesc->nodeType != XML_ELEMENT_NODE)
				continue;
			$arrayDesc[$fdesc->nodeName][] = $fdesc->nodeValue ;
		}
		
		
		$arrayStruct = null;
		$xp_struct = databox::get_xpath_structure($sbas_id);
		$recmstruct = $xp_struct->query("/record/description");	
		
		$stru = $recmstruct->item(0);
		for($fstru = $stru->firstChild; $fstru; $fstru = $fstru->nextSibling)
		{		
			if($fstru->nodeType != XML_ELEMENT_NODE)
				continue;
			
			foreach($fstru->attributes as $a)
			{
				if(!isset($arrayStruct[$fstru->nodeName]))
					$arrayStruct[$fstru->nodeName] = array();
				$arrayStruct[$fstru->nodeName][$a->name] = $a->value;			
			}
		}
	
		$t_iptc = null;	
		foreach( $arrayStruct as $tag=>$fieldpref )
		{
			if(isset($fieldpref["src"]) && (substr($fieldpref["src"],0,3)=="ip-" || true))
			{
				if($resetAllFields)
					$t_iptc[$fieldpref["src"]] = "";
	
				if( isset($arrayDesc[$tag]) )
				{
					$t_iptc[$fieldpref["src"]] = $arrayDesc[$tag];
					
					if($fieldpref["src"]=="ip-date")
					{
						$fieldpref["type"] = "date";
						foreach($t_iptc[$fieldpref["src"]] as $k=>$v)
						{
							$isodate = answer::getFieldValue($fieldpref, $v);
							$t_iptc[$fieldpref["src"]][$k] = substr($isodate, 0, 4) . "/" . substr($isodate, 4, 2) . "/" . substr($isodate, 6, 2);
						}
					}
				}
			}
		}
		
		if($debug)
			var_dump($t_iptc);
			
		if( $filesArray!=null )
		{
			$cmd_removeiptc = "";
			$cmd_removeiptcET = "";
			$cmd_setiptc = "";
			$cmd_setiptcET = "";
			if($t_iptc)
			{
				foreach($t_iptc as $src => $values)
				{
					if($src == "ip-keyword" || $src == "ip-suppcat")
						$cmd_removeiptc .= ($cmd_removeiptc == ""?"":", ") . $src;
					if(true)
					{
						if($src == "Keywords" || $src == "Contact")
							$cmd_removeiptcET .= "-" . $src . "= ";
					}
					foreach($values as $value)
					{
						if($debug)
							printf("src=%s value=%s\n", $src, $value);
						if($src=="ip-date")
						{
							
						}
						$f = $value;
						$f = str_replace(array("\n", "\r", "'", "\""), array("\\n"   , "", "\\'", "\\\""), $f);
						$cmd_setiptc .= ($cmd_setiptc == ""?"":", ") . "" . $src . "='" . $f . "'";				
						if(true)
						{
							$cmd_setiptcET .= "-" . $src . "='" . $f . "' ";
						}
					}
				}
			}
			
			foreach($filesArray as $doc)
			{
				if($cmd_removeiptc != "" || $cmd_removeiptcET != "")
				{
					$cmd = GV_exiftool ." \"" . $cmd_removeiptcET . "\" \"" . $doc . "\"" ;			
					passthru($cmd);
				}
				if($cmd_setiptc != "" || $cmd_setiptcET != "")
				{
					$cmd = GV_exiftool ." \"" . $cmd_setiptcET . "\" \"" . $doc . "\"" ;			
					passthru($cmd);
				}	
			}		
		}
		chdir($cwd);
	}
	
	public static function getFieldValue(&$sxStructField, $val)
	{
		$value = NULL;
	
		switch((string)$sxStructField["type"])
		{
			case "text":
				$value = $val;
				if(GV_debug)
					printf("getFieldValue('%s', '%s') = '%s'\n", (string)$sxStructField["type"], $val, $value===NULL ? "NULL" : $value);
				break;
			case "number":
				$value = "" . (0.0 + $val);
				if(GV_debug)
					printf("getFieldValue('%s', '%s') = '%s'\n", (string)$sxStructField["type"], $val, $value===NULL ? "NULL" : $value);
				break;
			case "date":
				$value = trim(str_replace(array("-", ":", "/", ".", " "), array("", "", "", "", ""), $val));	// jy 20060802 : le contenu (val) est deja en iso delimite
				if(GV_debug)
					printf("getFieldValue('%s', '%s') = '%s'\n", (string)$sxStructField["type"], $val, $value===NULL ? "NULL" : $value);
				break;
			default:
				break;
		}
		return($value);
	}
}