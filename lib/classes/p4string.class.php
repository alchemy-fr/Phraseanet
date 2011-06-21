<?php
class p4string
{
	
	public static function addFirstSlash($path)
	{
	  	if($path=="")
			return("./");
		$c = substr($path,0,1);
		if($c!="/" && $c!="\\")
			$path = "/".$path;
		return($path);
	}
	
	public static function delFirstSlash($path)
	{
	  	if($path=="/" || $path=="\\")
			return("");
		$c = substr($path,0,1);
		if($c=="/" || $c=="\\")
			$path = substr($path, 1, strlen($path));
		if($path=="")
			$path = "./";
		return($path);
	}
	
	public static function addEndSlash($path)
	{
	  	if($path=="")
			return("./");
		$c = substr($path,-1,1);
		if($c!="/" && $c!="\\")
			$path .= "/";
		return($path);
	}
	
	public static function delEndSlash($path)
	{
	  	if($path=="/" || $path=="\\")
			return("");
		$c = substr($path,-1,1);
		if($c=="/" || $c=="\\")
			$path = substr($path, 0, strlen($path)-1);
		if($path=="")
			$path = ".";
		return($path);
	}

	public static function cleanTags($string)
	{
		return strip_tags($string, '<p><a><b><i><div><ul><ol><li><br>');
	}

	public static function checkMail($adresse)
	{
	   $Syntaxe='#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#';
	   if(preg_match($Syntaxe,$adresse))
	      return true;
	   else
	     return false;
	}
	
	public static function encodeMailSubject($subject)
	{
		return("=?UTF-8?B?".base64_encode(html_entity_decode(str_replace(array("\r\n","\n","\r")," ",$subject), ENT_COMPAT, "UTF-8"))."?=\n");
	}
	
	

	public static function JSstring($s)
	{
		return(str_replace(array("\\", "\"", "\r", "\n"), array("\\\\", "\\\"", "\\r", "\\n"), $s));
	}
	
	public static function MakeString($s, $context='html', $quoted='')
	{
		switch(mb_strtolower($context.'_'.$quoted))
		{
			case 'js_':	// old method
				$s = str_replace(array("\\", "\"", "'", "\r", "\n"), array("\\\\", "\\\"", "\\'", "\\r", "\\n"), $s);
				break;
			case 'js_"':
				$s = str_replace(array("\\", "\"", "\r", "\n"), array("\\\\", "\\\"", "\\r", "\\n"), $s);
				break;
			case 'js_\'':
				$s = str_replace(array("\\", "'", "\r", "\n"),  array("\\\\", "\\'", "\\r", "\\n"), $s);
				break;
				
			case 'dquot_"':
				$s = str_replace(array("\\", "\"", "\r", "\n"), array("\\\\", "\\\"", "\\r", "\\n"), $s);
				break;
			case 'squot_"':
				$s = str_replace(array("\\", "'", "\r", "\n"), array("\\\\", "\\'", "\\r", "\\n"), $s);
				break;
				
			case 'html_':	// old method
			case 'html_\'':
			case 'html_"':
				$s = str_replace(array("&", "<", ">", "\n"), array("&amp;", "&lt;", "&gt;", "<br/>\n"), $s);
				break;
				
			case 'htmlprop_':
				$s = str_replace(array("\"", "'", "<", ">"), array("&quot;", "&#39;" , "&lt;", "&gt;"), $s);
				break;
			case 'htmlprop_\'':
				$s = str_replace(array("'", "<", ">"), array("&#39;"  , "&lt;", "&gt;"), $s);
				break;
			case 'htmlprop_"':
				$s = str_replace(array("\"", "<", ">"), array("&quot;", "&lt;", "&gt;"), $s);
				break;
				
			case 'form_':
			case 'form_\'':		// <input type... value='$phpvar'...>
			case 'form_"':
				$s = str_replace(array("&", "\"", "'", "<", ">"), array("&amp;", "&quot;", "&#39;" , "&lt;", "&gt;"), $s);
				break;
				
			case 'none_"':
			default:
				break;
		}
		return($s);
	}

	public static function cutDesc($title,$longueur,$beginHiLight,$endHiLight)
	{
		if( mb_strlen($title)<=$longueur )
			return $title;
		$oldtitle 	= $title;
		$newtitle 	= "";
		$lenBegin 	= mb_strlen($beginHiLight);
		$lenEnd 	= mb_strlen($endHiLight);
		$continu 	= true;
		$tabHiLight = NULL;
		$idx 		= 0 ;
		$tmpTitle 	= $title;
		$tmpTitle 	= $title;
		$lastPos 	= 0;
		$continu 	= true;
		while($continu)
		{
			if( ($positiionBegin=mb_stripos($tmpTitle,$beginHiLight))!==FALSE  )
			{
				$tabHiLight[$idx]["begin"]=$positiionBegin+$lastPos;
				$lastPos += ($positiionBegin+$lenBegin);
				$tmpTitle = mb_substr($tmpTitle, ($positiionBegin+$lenBegin));
				if(($positiionEnd=mb_stripos($tmpTitle, $endHiLight))!==FALSE )
				{
					$tabHiLight[$idx]["end"]=$positiionEnd+$lastPos;
					$idx++ ;
					$lastPos += ($positiionEnd+$lenEnd);
					$tmpTitle = mb_substr($tmpTitle, ($positiionEnd+$lenEnd));
				}
				else
					$continu = false;
			}
			else
				$continu = false;
		}
		if($tabHiLight)
		{
			for( $i=0; $i<count($tabHiLight); $i++ ) //$tabHiLight as $index=>$hl)
			{
				if($longueur> $tabHiLight[$i]["begin"] && $longueur< ($tabHiLight[$i]["end"]+$lenEnd) )
				{
					// on va couper ....
					// ...soit on coupe pile dans la balise ouvrante
					if( $longueur>$tabHiLight[$i]["begin"] && $longueur<($tabHiLight[$i]["begin"]+$lenBegin) )
					{
						// dans ce cas on zappe avant la balise
						$newtitle = mb_substr($title,0,$tabHiLight[$i]["begin"]) ;
						$i = count($tabHiLight);
					}
					elseif(  $longueur>($tabHiLight[$i]["end"]) && $longueur<($tabHiLight[$i]["end"]+$lenEnd)  ) // .....soit pile poil dans la balise fermante
					{
						// dans ce cas on met la fin de la balise et on zappe le reste
						$newtitle = mb_substr($title,0,$tabHiLight[$i]["end"]+$lenEnd) ;
						$i = count($tabHiLight);
					}
					else// ....on coupe entre 2 balises, pas de souci
					{
						$newtitle = mb_substr($title,0,$longueur) . $endHiLight;
						$i = count($tabHiLight);
					}
				}
				elseif( ($i+1)==count($tabHiLight)) // elseif($i>0)
				{
					//if(  $longueur>=($tabHiLight[$i-1]["end"]+$lenEnd)  &&  $longueur<=$tabHiLight[$i]["begin"] )
					//{
					//}
	
						// je suis a la fin du tableau d"e highlight et pas de coupe donc je suis ..
						// ... soit entre une balise fermante et une ouvrante
						// ... soit avant la premiere balise ouvrante
						// ... soit apres la derniere balise ouvrante
	
						// pas de souci
						$newtitle = mb_substr($title,0,$longueur);
						$i = count($tabHiLight);
				}
			}
		}
		else
		{
			// pas de highlight, on coupe ou on veut alors
			$newtitle = mb_substr($title,0,50);
		}
		if( mb_strlen($title)> mb_strlen($newtitle) )
			$newtitle = $newtitle."...";
		return $newtitle;
	}

	public static function entitydecode($string)
	{
		return str_replace(array('[[',']]'),array('<','>'),($string));	
	}
	
	public static function quotescode($string)
	{
		return str_replace(array('"','\''),array('&quot;','&apos;'),($string));
	}
	
	public static function hasAccent($string)
	{
		$ret = true;
		preg_match('/^[a-zA-Z0-9-_]+$/', $string, $matches);
		
		if(count($matches) == '1' && $matches[0] == $string)
			$ret = false;
			
		return $ret;
	}
	
	public static function jsonencode($datas)
	{
		if(version_compare(PHP_VERSION, '5.3.0') >= 0)
		{
			return json_encode($datas,JSON_HEX_TAG|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_APOS);
		}
		else
		{
			return json_encode($datas);
		}
	}
	
	public static function format_octets($octets, $precision = 2)
	{
		$octets = (int)$octets;
		if($octets < 900)
			return $octets.' o';
		$koctet = round($octets / 1024, $precision);
		if($koctet < 900)
			return $koctet.' ko';
		$Moctet = round($octets / (1024 * 1024), $precision);
		if($Moctet < 900)
			return $Moctet.' Mo';
		$Goctet = round($octets / (1024 * 1024 * 1024), $precision);
		if($Goctet < 900)
			return $Goctet.' Go';
		$Toctet = round($octets / (1024 * 1024 * 1024 * 1024), $precision);
		return $Toctet.' To';
	}
}