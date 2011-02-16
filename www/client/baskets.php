<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
require_once(GV_RootPath."lib/clientUtils.php");
$session = session::getInstance();

$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
}
else{
	header("Location: /login/client/");
	exit();
}

$request = httpRequest::getInstance();
$parm = $request->get_parms("bas" , "courChuId" , "act" ,"p0", "first");
	
$parm['p0'] = utf8_decode($parm['p0']);

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	die();

$nbNoview = 0;

$user = user::getInstance($session->usr_id);
$usrRight = NULL;
$usrRightSum = array('canputinalbum'=>0, 'candwnldhd'=>0, 'candwnldpreview'=>0, 'canpreview'=>0, 'cancmd'=>0);

$out = null ;

$conn = connection::getInstance();
if(!$conn)
	die();

$sql = 'SELECT bas.base_id, canputinalbum,canpreview, candwnldhd, candwnldpreview, cancmd FROM ((usr NATURAL JOIN basusr) INNER JOIN bas ON bas.base_id=basusr.base_id) WHERE basusr.actif>0 AND bas.active>0 AND usr.usr_id="'.$conn->escape_string($usr_id).'"';
if($rs = $conn->query($sql))
{
	while($row = $conn->fetch_assoc($rs))
	{
		$usrRightSum["canputinalbum"]	+= $row["canputinalbum"];
		$usrRightSum["candwnldhd"]		+= $row["candwnldhd"];
		$usrRightSum["candwnldpreview"] += $row["candwnldpreview"];
		$usrRightSum["canpreview"] += $row["canpreview"];
		$usrRightSum["cancmd"]          += $row["cancmd"];	
	}
	$conn->free_result($rs);
}
		
if( $parm["act"]=="DELIMG" && $parm["p0"]!="" )
{
	$basket = basket::getInstance($parm['courChuId']);
	$basket->remove_from_ssel($parm['p0']);
}

if( $parm["act"]=="ADDIMG" && ($parm["p0"]!="" &&$parm["p0"]!=null) )
{
	$basket = basket::getInstance($parm['courChuId']);
	$basket->push_element($parm['bas'], $parm['p0'], false, false);
}

if( $parm["act"]=="DELCHU" && ($parm["p0"]!="" &&$parm["p0"]!=null) )
{
	$basket = basket::getInstance($parm['p0']);
	$basket->delete();
	unset($basket);
}


if( $parm["act"]=="NEWCHU" && ($parm["p0"]!="" &&$parm["p0"]!=null) )
{
	$basket = new basket();
	$basket->name = $parm['p0'];
	$basket->save();
	$parm['courChuId'] = $basket->ssel_id;
}
$basket_coll = new basketCollection('name ASC',array('regroup'));//basket::getBaskets('name ASC',false);
$baskets = $basket_coll->baskets;

$out  = "<table style='width:99%' class='baskIndicator' id='baskMainTable'><tr><td>"; 
$out .= '<select id="chutier_name" name="chutier_name" onChange="chg_chu();" style="width:120px;">';
$firstGroup = true;

foreach($baskets as $typeBask=>$basket)
{
	
		if(!$firstGroup)
			$out.='</optgroup>';
		$firstGroup = false;	
		
		if($typeBask == 'baskets' && count($basket) > 0)
		{
			$out.='<optgroup label="'._('paniers::categories: mes paniers').'">';
			foreach($basket as $bask)
			{
				$baskId = $bask->ssel_id;
				$sltd ='';
				if(is_null($parm['courChuId']) || trim($parm['courChuId']) == '')
					$parm['courChuId'] = $baskId;
				if($parm['courChuId'] == $baskId)
					$sltd = 'selected';
				$out .= '<option class="chut_choice" '.$sltd.' value="'.$baskId.'">'.$bask->name.'</option>';	
			}
		}
		
		if($typeBask == 'recept' && !is_null($basket))
		{
			$out.='<optgroup label="'._('paniers::categories: paniers recus').'">';
			foreach($basket as $bask)
			{
				$baskId = $bask->ssel_id;
				$sltd ='';
				if(is_null($parm['courChuId']) || trim($parm['courChuId']) == '')
					$parm['courChuId'] = $baskId;
				if($parm['courChuId'] == $baskId)	
					$sltd = 'selected';
				$out .= '<option class="chut_choice" '.$sltd.' value="'.$baskId.'">'.$bask->name.'</option>';	
			}
		}
	
}
			$out.='</optgroup>';
		$out .= "</select>";
	$out .= '</td><td style="width:40%">'; 	
	

$basket = basket::getInstance($parm['courChuId']);

$jscriptnochu = $basket->name . " :  ".sprintf(_('paniers:: %d documents dans le panier'),count($basket->elements));
					
$nbElems = count($basket->elements);

?><div id="blocBask" class="bodyLeft" style="height:314px;bottom:0px;"><?php
	?><div class="baskTitle"><?php
		?><div id="flechenochu" class="flechenochu"></div><?php
	
			$totSizeMega = $basket->get_size();
			echo '<div class="baskName">'.sprintf(_('paniers:: paniers:: %d documents dans le panier'),$nbElems).(GV_viewSizeBaket?' ('.$totSizeMega.' Mo)':'').'</div>';
		
		 
	?></div><?php
	?><div><?php
					
			
echo $out;	


if($nbElems>0 && $basket->is_mine)
{
	?><div class="baskDel" title="<?php echo _('action : supprimer')?>" onclick="evt_chutier('DELSSEL');"/></div><?php
}
if($usrRightSum["canputinalbum"]>0 ) 
{
	?><div class="baskCreate" title="<?php echo _('action:: nouveau panier')?>" onclick="newBasket();"></div><?php
}
?><div style="float:right;position:relative;width:3px;height:16px;"></div><?php
if($nbElems>0 && ($usrRightSum["candwnldhd"]>0 || $usrRightSum["candwnldpreview"]>0 || $usrRightSum["cancmd"]>0 ))
{
	?><div class="baskDownload" title="<?php echo _('action : exporter')?>" onclick="evt_dwnl();"></div><?php
}
if($nbElems>0 )
{
	?><div class="baskPrint" title="<?php echo _('action : print')?>" onclick="evt_print();"></div><?php
}
$jsclick='';
if($parm['courChuId']!=null && $parm['courChuId']!= '' && is_numeric($parm['courChuId']))
{
	$jsclick = ' onclick=openCompare(\''.$parm['courChuId'].'\') ';
}	
?><div class="baskComparator" <?php echo $jsclick?> title="<?php echo _('action : ouvrir dans le comparateur')?>"></div><?php

			?></td><?php
		?></tr><?php
	?></table><?php
?></div><?php

?><div class="divexterne" style="height:270px;overflow-x:hidden;overflow-y:auto;position:relative"><?php

			
			if($basket->pusher > 0)
			{
				$pusher = user::getInfos($basket->pusher);
				
				?><div class="txtPushClient"><?php
						echo sprintf(_('paniers:: panier emis par %s'),$pusher)
				?></div><?php
			}
			
			foreach($basket->elements as $basket_element)
			{
			
				$dim  = $dim1 = $top  = 0 ;
						
				if($basket_element->width > $basket_element->height) // cas d'un format paysage
				{
					if($basket_element->width > 67)
					{
						$dim1 = 67;
						$top = ceil((67-67*$basket_element->height/$basket_element->width)/2);
					}
					else // miniature
					{
						$dim1 = $basket_element->width;
						$top = ceil((67-$basket_element->height)/2);
					}
					$dim = "width:" . $dim1 . "px";
				}
				else // cas d'un format portrait
				{
					if($basket_element->height > 55)
					{
						$dim1 = 55;
						$top = ceil((67-55)/2);
					}
					else // miniature
					{
						$dim1 = $basket_element->height;
						$top = ceil((67-$basket_element->height)/2);
					}
					$dim = "height:" . $dim1 . "px";
				}
				
				if($basket_element->height >42)
					$classSize = "hThumbnail";
				else	
					$classSize = "vThumbnail";		
				
				$minirollover = "";
					
				if(GV_rollover_chu)
				{
					$canpreview = false;
					if(isset($user->_rights_bas[$basket_element->base_id]) && $user->_rights_bas[$basket_element->base_id]['canpreview'])
						$canpreview = true;
					$minirollover = getMiniRollover($ses_id,$usr_id,array($basket_element->base_id,$basket_element->record_id),$canpreview);
				}
				?><div class="diapochu"><?php
				?><div class="image"><?php

				?><img onclick="openPreview('BASK',<?php echo $basket_element->order?>,<?php echo $parm["courChuId"]?>); return(false);" 
					title="<?php echo str_replace('"','&quot;',$minirollover)?>" style="position:relative; top:<?php echo $top?>px; <?php echo $dim?>" 
					class="<?php echo $classSize?> baskTips" src="<?php echo $basket_element->url?>"><?php

				?></div><?php
				?><div class="tools"><?php

				if($basket->is_mine)//le panier est a moi, je peux effacer des elements
				{
					?><div class="baskOneDel" onclick="evt_del_in_chutier('<?php echo $basket_element->sselcont_id?>');" 
					title="<?php echo _('action : supprimer')?>"></div><?php
				}
								
				if(
					(isset($usrRight->_rights_bas[$basket_element->base_id])
						 && ($usrRight->_rights_bas[$basket_element->base_id]["candwnldhd"] || 
						 	 $usrRight->_rights_bas[$basket_element->base_id]["candwnldpreview"] || 
						 	 $usrRight->_rights_bas[$basket_element->base_id]["cancmd"]))
					|| in_array($basket_element->base_id.' '.$basket_element->record_id,$user->_rights_records)) 
				{ 
					?><div class="baskOneDownload" onclick="evt_dwnl('<?php echo $basket_element->base_id?>_<?php echo $basket_element->record_id?>');" title="<?php echo _('action : exporter')?>"></div><?php
				}
				
				?></div><?php
				?></div><?php
			}
?></div></div><div id="blocNoBask" class="bodyLeft" style="height: 22px;display:none;bottom:0px;"><?php
			?><div class="baskTitle"><?php
				?><div id="flechechu" class="flechenochu"></div><?php
				?><div id="viewtext" class="baskName"><?php echo $jscriptnochu?><span style="width:16px;height:16px;position: absolute; right: 10px;background-position:center center;" class='baskIndicator'></span></div><?php
			?></div><?php
		?></div>
		<?php

		?>
		<script>
			var oldNoview = p4.nbNoview;
			p4.nbNoview = parseInt(<?php echo $nbNoview?>);		
			if(p4.nbNoview>oldNoview)
				alert('<?php echo _('paniers:: vous avez de nouveaux paniers non consultes');?>');
		</script>