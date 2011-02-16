<?php
	
	 
	$th_size = user::getPrefs('images_size');
	foreach($rsScreen as $data)
	{
		$ident = $data["base_id"]."_".$data["record_id"];
		
		$data['selected'] = in_array($ident,$parm['sel'])?true :false;
		$c = $data["grouping"]?'grouping ':'';
		$c .= $data['selected']? "selected " : "";
		$c .= ' type-'.$data['type'];
		
		if($parm['search_type'] == '1')
			echo  "<div style='width:".($th_size+30)."px;' sbas=\"".$data['sbas']."\" id='IMGT_".$data['base_id']."_".$data['record_id']."' class='IMGT diapo ".$c."' onDblClick=\"openPreview('REG','0','".$data['base_id']."_".$data['record_id']."');\">";
		else
			echo  "<div style='width:".($th_size+30)."px;' sbas=\"".$data['sbas']."\" id='IMGT_".$data['base_id']."_".$data['record_id']."' class='IMGT diapo ".$c."' onDblClick=\"openPreview('RESULT',".$data['number'].");\">";
		
		
		echo  '<div style="height:40px; position: relative; z-index: 100;">';
			echo  "<div class=\"title\">";


		echo  $data['title'];

		echo  "</div>\n";
		

		echo  "<div class=\"status\">";
		echo  $data['status'];
		echo  "</div>\n";
		echo  '</div>';

		echo  "<div class=\"thumb ".($data['rollover_gif'] ? "rollovable":"")."\" style=\"height:".(int)$th_size."px;z-index:90;\">\n";

		$caption = $data['caption'] . (user::getPrefs('technical_display') == 'group' ? '<hr/>'.$data['infos'] : '');
		
		$th_title = $th_rollover = '';
		if(user::getPrefs('rollover_thumbnail') == 'caption')
			$th_title = $caption;
		if(user::getPrefs('rollover_thumbnail') == 'preview' && $data['preview'] != '')
			$th_title = $data['preview'];
			
		if($data['rollover_gif'])
		{
			$th_rollover = $th_title;
			$th_title = '';
		}
			
		echo  $data['duration'].'<img ondragstart="return false;" title="'.str_replace(array('&','"'),array('&amp;','&quot;'),$th_title).'" class="IMGT_'.$data['base_id'].'_'.$data['record_id'].' '.$data['imgclass'].' '.($data['rollover_gif'] ? "rollover-gif-out":"").' '.(trim($th_title) != '' ? "captionTips":"").'" src="'.$data['thumb'].'" style="'.$data['imgstyle'].'" />';
		if($data['rollover_gif'])
			echo '<img ondragstart="return false;" title="'.str_replace(array('&','"'),array('&amp;','&quot;'),$th_rollover).'" class="IMGT_'.$data['base_id'].'_'.$data['record_id'].' '.$data['imgclass'].' rollover-gif-hover '.(trim($th_rollover) != '' ? "captionTips":"").'" src="'.$data['rollover_gif']['src'].'" style="display:none;'.$data['rollover_gif']['style'].'"/>';
				
		echo  "</div>";
			
		echo  '<div style="height: 25px;position:relative;text-align:left;">';
		echo  '<table class="bottom" style="width:100%;table-layout:auto;">';
		echo  '<tr>';
		echo  '<td style="text-align:left;">';

		echo  $data['logo'];
			
		echo  "</td>\n";

		
		$l_width = 30 + (user::getPrefs('rollover_thumbnail') == 'preview' ? 20 : 0)+(user::getPrefs('technical_display') == '1' ? 20:0);
		
		echo  "<td style='text-align:right;width:".$l_width."px;' valign='bottom'>\n";
		
		echo  $data['share'];
		
		if(trim($data['preview']) != '')
			echo '<div title="'.str_replace(array('&','"'),array('&amp;','&quot;'),$data['preview']).'" class="previewTips"></div>';
		
		if(user::getPrefs('rollover_thumbnail') == 'preview')
			echo '<div title="'.str_replace(array('&','"'),array('&amp;','&quot;'),$caption).'" class="captionRolloverTips"></div>';
		
		if(user::getPrefs('technical_display') == '1')
			echo  "<img class=\"infoTips\" title=\"".($data['infos'])."\" src=\"/skins/icons/info.gif\"/>";
		
		
		echo  "</td>";
		echo  "</tr>";
		echo  "</table>";
		echo  "</div>";
			
			
		echo  "</div>";
	}

?>
