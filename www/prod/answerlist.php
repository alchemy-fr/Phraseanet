<?php
	$th_size = user::getPrefs('images_size');
	foreach($rsScreen as $data)
	{
		$ident = $data["base_id"]."_".$data["record_id"];
		$data['selected'] = in_array($ident,$parm['sel'])?true :false;
		$c = $data["grouping"]?'grouping ':'';
		$c .= $data["selected"]? "selected ":"";
		$c .= ' type-'.$data['type'];
			
		echo  "<div class='list ui-corner-all'>";
		
		if($parm['search_type'] == '1')
			echo  "<table style='width:100%;' cellspacing='0' cellpadding='0' border='0'><tr><td valign=\"top\" style='width:".($th_size+50)."px'>
				<div style='border:none;position:relative;width:".($th_size+30)."px' class='diapo IMGT ".$c."' sbas='".$data["sbas"]."' id='IMGT_".$data["base_id"]."_".$data["record_id"]."' onDblClick=\"openPreview('REG','0','".$data["base_id"]."_".$data["record_id"]."');\">";
		else
			echo  "<table style='width:100%;' cellspacing='0' cellpadding='0' border='0'><tr><td valign=\"top\" style='width:".($th_size+50)."px'>
				<div style='border:none;position:relative;width:".($th_size+30)."px' class='diapo IMGT ".$c."' sbas='".$data["sbas"]."' id='IMGT_".$data["base_id"]."_".$data["record_id"]."' onDblClick=\"openPreview('RESULT',".$data["number"].");\">";
		
		echo  '<div style="height:40px;position: relative; z-index: 100;">';
		
			echo  "<div class='title'>".$data["title"]."</div>";
			echo  "<div class='status'>".$data["status"]."</div>";
		
		echo  "</div>";			
		
		echo  "<div class='thumb ".($data['rollover_gif'] ? "rollovable":"")."' style='height:".$th_size."px;z-index:90;'>";
		
		echo  $data["duration"].'<img ondragstart="return false;" style="'.$data['imgstyle'].';" class="'.$data['imgclass'].' IMGT_'.$data['base_id'].'_'.$data['record_id'].' '.($data['rollover_gif'] ? "rollover-gif-out":"").'" src="' . $data['thumb'].'" />';
			
		if($data['rollover_gif'])
			echo '<img ondragstart="return false;" class="IMGT_'.$data['base_id'].'_'.$data['record_id'].' '.$data['imgclass'].' rollover-gif-hover" src="'.$data['rollover_gif']['src'].'" style="display:none;'.$data['rollover_gif']['style'].'"/>';
			
		
		echo  "</div>\n";
		echo  "<div style='height:25px;position:relative;text-align:left;'>";
		
		echo  "<table class='bottom' width=\"100%;table-layout:auto;\">";			
		echo  "<tr>";			
		echo  "<td style='text-align:left'>";			

		echo  $data["logo"];
		
		echo  "</td>";
		echo  "<td style='text-align:right;width:60px;' valign=\"bottom\">";

		echo  $data['share'];
		
//		if(user::getPrefs('rollover_thumbnail') == 'caption' && $data['preview'] != '')
			echo '<div title="'.str_replace(array('&','"'),array('&amp;','&quot;'),$data['preview']).'" class="previewTips"></div>';
			
		if(user::getPrefs('technical_display') == '1')
			echo  "<img class=\"infoTips\" title=\"".($data['infos'])."\" src=\"/skins/icons/info.gif\"/>";
		
		echo  "</td>";
		echo  "</tr>";
		echo  "</table>\n";
		
		echo  "</div>";
		echo  "</div></td><td valign=\"top\">\n";

		echo  "<div class='desc' style='max-height:".($th_size+70)."px;overflow-y:auto;'>";

				
		echo  "<div class=\"fixeddesc\">".$data["caption"].(user::getPrefs('technical_display') == 'group' ? '<hr/>'.$data['infos'] : '')."</div></div></td></tr></table>";
		echo  "</div>";
			
		 
	}
	
?>
