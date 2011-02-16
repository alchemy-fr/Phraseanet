<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		header("Location: /login/?err=no-session");
		exit();
	}
}
else
{
	header("Location: /login/");
	exit();
}


$request = httpRequest::getInstance();
$parm = $request->get_parms(
					'act',
					'sbas',
					'presetid',
					'title',
					'f',
					'debug'
				);
				
$ret = array('parm'=>$parm);

$conn = connection::getInstance();

if($conn)
{
	switch($parm['act'])
	{
		case 'DELETE':
			$sql = 'DELETE FROM edit_presets WHERE edit_preset_id=\'' . $conn->escape_string($parm['presetid']) . '\'';
			$conn->query($sql);
			$ret['html'] = xlist($conn);
			break;
		case 'SAVE':
			$dom = new DOMDocument('1.0', 'UTF-8');
			$dom->standalone = true;
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			
			$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?><edit_preset>' . $parm['f'] . '</edit_preset>';
			$dom->loadXML($xml);
			
			$sql = 'INSERT INTO edit_presets (creation_date, sbas_id, usr_id, title, xml) VALUES (NOW(), \''.
					$conn->escape_string($parm['sbas']) . '\',\'' .
					$conn->escape_string($usr_id) . '\',\'' .
					$conn->escape_string($parm['title']) . '\',\'' .
					$conn->escape_string($dom->saveXML()) . '\')';
			$conn->query($sql);
			$ret['html'] = xlist($conn);
			break;
		case 'LIST':
			$ret['html'] = xlist($conn);
			break;
		case "LOAD":
			$sql = 'SELECT edit_preset_id, creation_date, title, xml FROM edit_presets WHERE edit_preset_id=\'' . $conn->escape_string($parm['presetid']) . '\'';
			$fields = array();
			if($rs = $conn->query($sql))
			{
				if( ($row = $conn->fetch_assoc($rs)) )
				{
					if( ($sx = simplexml_load_string($row['xml'])) )
					{
						foreach($sx->fields->children() as $fn=>$fv)
						{
							if(!array_key_exists($fn, $fields))
								$fields[$fn] = array();
							$fields[$fn][] = trim($fv);
						}
					}
				}
				$conn->free_result($rs);
			}
			$ret['fields'] = $fields;
			break;
	}
}

function xlist(&$conn)
{
	global $parm;
	$session = session::getInstance();
	$html = '';
	$sql = 'SELECT edit_preset_id, creation_date, title, xml FROM edit_presets WHERE usr_id=\'' . $conn->escape_string($session->usr_id)
			. '\' AND sbas_id=\'' . $conn->escape_string($parm['sbas']) . '\' ORDER BY creation_date ASC';
	if($rs = $conn->query($sql))
	{
		while( ($row = $conn->fetch_assoc($rs)) )
		{
			if( ($sx = simplexml_load_string($row['xml'])) )
			{
				$t_desc = array();
				foreach($sx->fields->children() as $fn=>$fv)
				{
					if(!array_key_exists($fn, $t_desc))
						$t_desc[$fn] = trim($fv);
					else
						$t_desc[$fn] .= ' ; ' . trim($fv);
				}
				$desc = '';
				foreach($t_desc as $fn=>$fv)													
					$desc .= '		<p><b>' . $fn . ':&nbsp;</b>' . str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $fv) . '</p>' . "\n";

				ob_start();
?>
				<LI id="EDIT_PRESET_<?php echo $row['edit_preset_id']?>">
					<h1 style="position:relative; top:0px; left:0px; width:100%; height:auto;">
						<a class="triangle" href="#"><span class='triRight'>&#x25BA;</span><span class='triDown'>&#x25BC;</span></a>
						<a class="title" href="#"><?php echo $row['title'] ?></a>
						<a class="delete" style="position:absolute;right:0px;" href="#"><?php echo _('boutton::supprimer')?></a>
					</h1>
					<DIV>
						<?php echo $desc ?>
					</DIV>
				</LI>
<?php
				$html .= ob_get_clean();
			}
		}
		$conn->free_result($rs);
	}
	return($html);
}

if(!$parm['debug'])
	print(p4string::jsonencode($ret));


?>