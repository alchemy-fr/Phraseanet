<?php

$session = session::getInstance();
$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		exit();
	}
}
else
{
	exit();
}
	

if(!$ph_session = phrasea_open_session($ses_id, $usr_id))
	die();
	
$conn = connection::getInstance();

$sbas_list = array();
$sbas_list = array();
$sbas = array();
foreach($ph_session['bases'] as $base)
{
	$sbas['s'+$base['sbas_id']] = array('sbid'=>$base['sbas_id'], 'seeker'=>null);
	$sbas_list[] = $base['sbas_id'];
}

$user = user::getInstance($session->usr_id);

$thesaurusFound = $candidatesFound = 0;

$htmlTXTree = $htmlCXTree = '';
$sql = "SELECT sbas.sbas_id FROM bas JOIN sbas ON bas.active>0 AND (sbas.sbas_id='".implode("' OR sbas.sbas_id='",$sbas_list)."') AND sbas.sbas_id=bas.sbas_id GROUP BY sbas_id";
if($rs = $conn->query($sql))
{
	$ntxbas = $ncxbas = $conn->num_rows($rs);
	while($row = $conn->fetch_assoc($rs))
	{
		$connbas = connection::getInstance($row['sbas_id']);
		if($connbas)
		{
			$sbas_id = $row["sbas_id"];
			
			$thesaurus = databox::get_sxml_thesaurus($sbas_id);
			if($thesaurus)
			{
				$class = --$ntxbas > 0 ? 'expandable' : 'expandable last';
				$htmlTXTree .= '<li id="TX_P.'.$sbas_id.'.T" class="'.$class.'">';
				$htmlTXTree .= '<div class="hitarea expandable-hitarea"></div>';
				$htmlTXTree .= '<span>' . phrasea::sbas_names($row["sbas_id"]) . '</span>';
				$htmlTXTree .= '<ul style="display:none">loading</ul>';
				$htmlTXTree .= '</li>';
				
				$thesaurusFound++;
			}
			
			if($user->_rights_sbas[$sbas_id]['bas_modif_th'])
			{
			
				$sql = "SELECT value FROM pref WHERE prop='cterms'";
				if($rsbas = $connbas->query($sql))
				{
					if($rowbas = $connbas->fetch_assoc($rsbas))
					{
						if(trim($rowbas["value"]) != '' && simplexml_load_string($rowbas["value"]) )
						{
							$class = --$ncxbas > 0 ? 'expandable' : 'expandable last';
							$htmlCXTree .= '<li id="CX_P.'.$sbas_id.'.C" class="'.$class.'">';
							$htmlCXTree .= '<div class="hitarea expandable-hitarea"></div>';
							$htmlCXTree .= '<span>' . phrasea::sbas_names($row["sbas_id"]) . '</span>';
							$htmlCXTree .= '<ul style="display:none">loading</ul>';
							$htmlCXTree .= '</li>';
							$candidatesFound++;
						}
					}
					$connbas->free_result($rsbas);
				}
			}
		}
	}
	$conn->free_result($rs);
}

if($thesaurusFound > 0)
{
	?>
	<div id="THPD_tabs">
	    <ul>
	        <li><a href="#THPD_T"><span><?php echo _('prod::thesaurusTab:thesaurus')?></span></a></li>
	        <?php 
	        if($candidatesFound > 0)
	        {
	        ?>
	        <li><a href="#THPD_C"><span><?php echo _('prod::thesaurusTab:candidats')?></span></a></li>
	        <?php 
	        }
	        ?>
	    </ul>
	    <div class="ui-tabs-panels-container">
			<div id="THPD_T">
				<div id='THPD_WIZARDS' style="position:relative; top:0px; left:0px; height:auto; width:100%; xbackground-color:#ffff00">
					<div class="wizard wiz_0">
						<!-- empty wizard -->
					</div>
					<div class="wizard wiz_1" style="display:none">
						<div class="txt"><?php echo _('prod::thesaurusTab:wizard:accepter le terme candidat')?></div>
					</div>
					<div class="wizard wiz_2" style="display:none">
						<div class="txt"><?php echo _('prod::thesaurusTab:wizard:remplacer par le terme')?></div>
					</div>
					<form class="gform" href="#" onsubmit="T_Gfilter(this);return(false);">
						<input type="text" onkeyup="T_Gfilter_delayed(this.value, 300)" style="width:150px;" />
						<input class="th_ok"     type="submit" value="<?php echo _('boutton::rechercher')?>" />
						<input class="th_cancel" type="button" value="<?php echo _('boutton::annuler')?>" onclick="thesauCancelWizard();return(false);"/>
					</form>
				</div>
				<div id='THPD_T_treeBox' class="searchZone" style="position:absolute; top:0px; bottom:0px; left:0px; width:100%; xbackground-color:#003f00; overflow:auto">
					<div onclick="Xclick(event);return(false);" ondblclick="TXdblClick(event);">
						<ul class="treeview" id="THPD_T_tree">
							<?php echo $htmlTXTree?>
						</ul>
					</div>
				</div>
		    </div>
	        <?php 
	        if($candidatesFound > 0)
	        {
	        ?>
		    <div id="THPD_C">
				<div id='THPD_C_treeBox' class="searchZone">
					<div onclick="Xclick(event);return(false);" ondblclick="CXdblClick(event);">
						<ul class="treeview" id="THPD_C_tree">
							<?php echo $htmlCXTree?>
						</ul>
					</div>
				</div>
		    </div>
		    <?php 
	        }
		    ?>
		</div>
	</div>
	<div style="display:none" id="THPD_confirm_del_dlg">
		<div id="THPD_confirm_del_dlg_msg"><?php echo _('prod::thesaurusTab:dlg:supprimer le terme ?')?></div>
	</div>
	<div style="display:none" id="THPD_confirm_accept_dlg">
		<div id="THPD_confirm_accept_dlg_msg"><?php echo _('prod::thesaurusTab:dlg:accepter le terme ?')?></div>
	</div>
	<div style="display:none" id="THPD_confirm_replace_dlg">
		<div id="THPD_confirm_replace_dlg_msg"><?php echo _('prod::thesaurusTab:dlg:remplacer le terme ?')?></div>
	</div>
<?php 
	
}
?>