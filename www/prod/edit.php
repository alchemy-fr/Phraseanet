<?php
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
$session = session::getInstance();
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

$request = httpRequest::getInstance();
$parm = $request->get_parms(
                'css'
                , 'mds'
                , 'lst'  // a list of bid_rid (selection)
                , 'ssel' // a ssel_id
                , 'act'  // if act==EDITNEWREG...
                , 'basid' // ... base where to create a new grp
                , 'desc' // ...
                , 'nam'  // ...
);


$lng = isset($session->locale) ? $session->locale : GV_default_lng;

if (isset($session->usr_id) && isset($session->ses_id))
{
  $ses_id = $session->ses_id;
  $usr_id = $session->usr_id;

  if (!($ph_session = phrasea_open_session((int) $ses_id, $usr_id)))
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

$usrRight = null;

$user = user::getInstance($session->usr_id);

$cssfile = '000000';
if (user::getPrefs('css'))
  $cssfile = user::getPrefs('css');

$editreg = null;

$conn = connection::getInstance();

$editwhat = "LST";

$sql = "SELECT base_id,canmodifrecord FROM (usr NATURAL JOIN basusr ) WHERE usr.usr_id='" . $conn->escape_string($usr_id) . "' and actif=1";

$sql = "SELECT basusr.base_id, canmodifrecord, sbasusr.bas_chupub, sbasusr.sbas_id
		FROM (usr NATURAL JOIN basusr)
		INNER JOIN bas ON basusr.base_id=bas.base_id
		INNER JOIN sbasusr on sbasusr.usr_id='" . $conn->escape_string($usr_id) . "' and sbasusr.sbas_id=bas.sbas_id
		WHERE usr.usr_id='" . $conn->escape_string($usr_id) . "' and actif=1";

if ($rs = $conn->query($sql))
{
  while ($row = $conn->fetch_assoc($rs))
  {
    $usrRight[$row['base_id']] = $row["canmodifrecord"];
  }
  $conn->free_result($rs);
}

$_sbase = array(); // tableau de parametres de (s)base, key:sbas_id

$lb = phrasea::bases();
foreach ($lb["bases"] as $ibase => $onebase)
{
  $_sbase[$onebase["sbas_id"]] = $onebase;
  $_sbase[$onebase["sbas_id"]]['_bases'] = array();

  foreach ($onebase["collections"] as $oneColl)
  {
    $_sbase[$onebase["sbas_id"]]['_bases'][$oneColl['base_id']] = $oneColl;
    $_sbase[$onebase["sbas_id"]]['_bases'][$oneColl['base_id']]['_dumped'] = false;
  }
  unset($_sbase[$onebase["sbas_id"]]["collections"]);  // remplace par '_bases'
}

$_lst = array(); // tableau des records en editing


$nb_rec_onstart = 0;  // le nb de records dans la liste
$nb_rec_can_edit = 0;  // le nb de records que j'ai le droit d'editer
$nb_rec_cant_edit = 0;  // le nb de records que je n'ai pas le droit d'editer
$same_sbas_id = '?';     // le sbas_id des fiches !!! 20081027 : editing multibases interdit !!!

if ($parm['ssel'] != '')
{
  // edit content of a basket
  $sql = 'SELECT temporaryType, rid, sbas_id FROM ssel WHERE ssel_id="' . $conn->escape_string($parm['ssel']) . '"';

  $rowssel = null;
  if ($rs = $conn->query($sql))
  {
    $rowssel = $conn->fetch_assoc($rs);
    $conn->free_result($rs);
  }
  if ($rowssel && $rowssel['temporaryType'] == 0)
  {
    // normal basket
    $sql = 'SELECT sselcont_id, base_id, record_id FROM sselcont WHERE ssel_id="' . $conn->escape_string($parm['ssel']) . '" ORDER BY ord ASC';

    if ($rs = $conn->query($sql))
    {
      while ($row = $conn->fetch_assoc($rs))
      {
        $bid = $row['base_id'];
        $rid = $row['record_id'];

        $nb_rec_onstart++;

        if ($same_sbas_id === '?')
          $same_sbas_id = phrasea::sbasFromBas($bid);
        else
        if ($same_sbas_id !== false && phrasea::sbasFromBas($bid) != $same_sbas_id)
          $same_sbas_id = false;

        if ($usrRight[$bid] == 1)
        {
          // j'ai le droit d'editer sur cette base
          $nb_rec_can_edit++;
          $_lst[] = array('bid' => $bid, 'rid' => $rid, 'sselcont_id' => $row['sselcont_id'], '_selected' => false);
        }
        else
        {
          $nb_rec_cant_edit++;
        }
      }
      $conn->free_result($rs);
    }
    $editwhat = "SSEL";
  }
  elseif ($rowssel && $rowssel['temporaryType'] == 1)
  {
    $same_sbas_id = $rowssel['sbas_id'];
    $zbase = $_sbase[$same_sbas_id];
    $coll_id = null;
    $connbas = connection::getInstance($same_sbas_id);
    if ($connbas)
    {
      $sql = 'SELECT coll_id FROM record WHERE record_id="' . $connbas->escape_string($rowssel['rid']) . '"';
      if ($rsbas = $connbas->query($sql))
      {
        if ($rowbas = $conn->fetch_assoc($rsbas))
          $coll_id = $rowbas['coll_id'];
        $connbas->free_result($rsbas);
      }
    }
    $bid = null;
    if ($coll_id !== null)
    {
      foreach ($zbase['_bases'] as $base)
      {
        if ($base['coll_id'] == $coll_id)
        {
          $bid = $base['base_id'];
          if ($usrRight[$bid] == 1)
          {
            // j'ai le droit d'editer sur cette base
            $nb_rec_can_edit++;
            $_lst[] = array('bid' => $bid, 'rid' => $rowssel['rid'], 'sselcont_id' => null, '_selected' => false);
          }
          break;
        }
      }
    }
  }
}
elseif ($parm['lst'] != '')
{

  foreach (explode(";", $parm["lst"]) as $basrec)
  {
    if (count($tmp = explode("_", $basrec)) == 2)
      list($bid, $rid) = $tmp;
    else
      continue;
    if ($bid === '' || $bid === null || $rid === '' || $rid === null)
      continue;

    $nb_rec_onstart++;

    if ($same_sbas_id === '?')
      $same_sbas_id = phrasea::sbasFromBas($bid);
    else
    if ($same_sbas_id !== false && phrasea::sbasFromBas($bid) != $same_sbas_id)
      $same_sbas_id = false;

    if (isset($usrRight[$bid]) && $usrRight[$bid] == 1)
    {
      // j'ai le droit d'editer sur cette base
      $nb_rec_can_edit++;
      $_lst[] = array('bid' => $bid, 'rid' => $rid, 'sselcont_id' => null, '_selected' => false);
    }
    else
    {
      $nb_rec_cant_edit++;
    }
  }
}

if ($same_sbas_id === false)
{
?>
  <script type="text/javascript">
    alert("<?php echo _('prod::edit: Impossible d\'editer simultanement des documents provenant de bases differentes') ?>");
    $('#EDITWINDOW').hide();
    hideOverlay(2);
  </script>
<?php
  die();
}

$regbasprid = 'false';
if ($nb_rec_can_edit == 1) // == count($_lst)==1
{
  $bid = $_lst[0]['bid'];
  $rid = $_lst[0]['rid'];

  if (phrasea_isgrp($ses_id, $bid, $rid))
  {

    $oneSbasid = phrasea::sbasFromBas($bid);
    $mygrp = phrasea_grpforselection($ses_id, $bid, $rid, GV_sit, $usr_id);

    $editreg["coll_idLoc"] = $mygrp[0][0];
    $editreg["record_id"] = $mygrp[0][1];
    $editreg["xmlstruct"] = $_sbase[$oneSbasid]["xmlstruct"];
    $editreg["xml"] = $mygrp[0][2];

    $editreg["coll_id"] = $_sbase[$oneSbasid]['_bases'][$bid]['coll_id'];
    $editreg["status"] = bindec(phrasea_status($ses_id, $mygrp[0][0], $mygrp[0][1]));

    if ($son = phrasea_grpchild($ses_id, $bid, $rid, GV_sit, $usr_id, TRUE))
    {
// printf("/* \n %s:\n%s \n */\n", __LINE__, var_export($son, true));
      $regbasprid = $bid . '_' . $rid;
      $mylist = "";
      $iord = 0;
      foreach ($son as $one)
      {
        if (is_array($one))
        {
          $_lst[] = array('bid' => $one[0], 'rid' => $one[1], 'sselcont_id' => null, '_selected' => false);
        }
      }
    }
    $editwhat = "GRP";
  }
}


$lstokcount = 0;

if (count($_lst) > 0)
{
  // comme on a un sbas_id unique, on y accede rapidement
  $zbase = $_sbase[$same_sbas_id];
?>
  <script  type="text/javascript">
    p4.edit.diapoSize = <?php echo user::getPrefs('editing_images_size') ?>;
<?php
  $T_sgval = array();

// on parse la structure via l'exploration simplexml
  $_tfields = array();
  $_tstatbits = array();

  $hasThesaurus = false;

  if (($sxe = databox::get_sxml_structure($same_sbas_id)) !== false)
  {
    $z = $sxe->xpath('/record/description');
    if ($z && is_array($z))
    {
      $allValues = "";
      $i = 0;
      foreach ($z[0] as $ki => $vi)
      {
        if (p4field::isyes($vi["readonly"]))
          continue;

        $_tfields[$ki] = array();

        $_tfields[$ki]['_idx'] = $i;
        $_tfields[$ki]['name'] = $ki;
        $_tfields[$ki]['_status'] = 0;
        $_tfields[$ki]['_value'] = "";
        $_tfields[$ki]['_sgval'] = array();

        $_tfields[$ki]['required'] = p4field::isyes($vi["required"]);
        $_tfields[$ki]['readonly'] = p4field::isyes($vi["readonly"]); // stupid

        $_tfields[$ki]['type'] = $type = (string) ($vi["type"]);
        $format = $explain = "";
        if ($type != "")
        {
          switch ($type)
          {
            case 'datetime':
              $format = _('phraseanet::technique::datetime-edit-format');
              $explain = _('phraseanet::technique::datetime-edit-explain');
              break;
            case 'date':
              $format = _('phraseanet::technique::date-edit-format');
              $explain = _('phraseanet::technique::date-edit-explain');
              break;
            case 'time':
              $format = _('phraseanet::technique::time-edit-format');
              $explain = _('phraseanet::technique::time-edit-explain');
              break;
          }
        }
        $_tfields[$ki]['format'] = $format;
        $_tfields[$ki]['explain'] = $explain;

        if (($_tfields[$ki]['tbranch'] = (string) ($vi["tbranch"])))
          $hasThesaurus = true;

        $_tfields[$ki]['regfield'] = (p4field::isyes($vi["regname"]) || p4field::isyes($vi["regdesc"]) || p4field::isyes($vi["regdate"]));

        $_tfields[$ki]['multi'] = p4field::isyes($vi["multi"]);
        $i++;
      }
    }
//				$z = $sxe->xpath('/record/statbits/bit');
//				if($z && is_array($z))
//				{
//					foreach($z as $bit)
//					{
//						$n = 0+$bit['n'];
//						if($n >=4 && $n <= 63)
//						{
//							$_tstatbits[$n] = array();
//							$_tstatbits[$n]['label0'] = $bit['labelOff'] ? $bit['labelOff'] : ('non-' . (string)$bit);
//							$_tstatbits[$n]['label1'] = $bit['labelOn'] ? $bit['labelOn'] : (string)$bit;
//							$_tstatbits[$n]['_value'] = 0;
//						}
//					}
//				}
  }

  if ($user->_global_rights['changestatus'])
  {
    $status = status::getDisplayStatus();
    if (isset($status[$same_sbas_id]))
    {
      foreach ($status[$same_sbas_id] as $n => $statbit)
      {
        $_tstatbits[$n] = array();
        $_tstatbits[$n]['label0'] = $statbit['labeloff'];
        $_tstatbits[$n]['label1'] = $statbit['labelon'];
        $_tstatbits[$n]['img_off'] = $statbit['img_off'];
        $_tstatbits[$n]['img_on'] = $statbit['img_on'];
        $_tstatbits[$n]['_value'] = 0;
      }
    }
  }
?>
  p4.edit.T_statbits = <?php echo p4string::jsonencode($_tstatbits); ?>;
  p4.edit.T_fields = <?php echo p4string::jsonencode(array_values($_tfields)); ?>;
<?php
  foreach ($_lst as $basrec)
  {
    $bid = $basrec['bid'];
    $rid = $basrec['rid'];

    if (!$zbase['_bases'][$bid]['_dumped'])
    {
      // une nouvelle collection dans la liste
      $T_sgval['b' . $bid] = array();
      if ($sxe = simplexml_load_string($zbase['_bases'][$bid]['prefs']))
      {
        $z = $sxe->xpath('/baseprefs/sugestedValues');
        if ($z && is_array($z))
        {
          foreach ($z[0] as $ki => $vi) // les champs
          {
            if (!isset($_tfields[$ki]))
              continue; // champ inconnu dans la structure ?
            if ($vi)
            {
              $T_sgval['b' . $bid][$_tfields[$ki]['_idx']] = array();
              foreach ($vi->value as $oneValue) // les valeurs sug
              {
                $T_sgval['b' . $bid][$_tfields[$ki]['_idx']][] = (string) $oneValue;
              }
            }
          }
        }
      }
      $zbase['_bases'][$bid]['_dumped'] = true;
    }
  }

  $connbas = connection::getInstance($same_sbas_id);


  if ($connbas)
  {
    foreach ($_lst as $idia => $basrec)
    {
      $bid = $basrec['bid'];
      $rid = $basrec['rid'];
      $cid = $zbase['_bases'][$bid]['coll_id'];

      $xml = '';
      if (!isset($zbase['_bases'][$bid]['_masks']))
      {
        $sql = 'SELECT mask_and, mask_xor FROM basusr WHERE usr_id="' . $conn->escape_string($usr_id) . '" AND base_id="' . $conn->escape_string($bid) . '"';
        if ($rs = $conn->query($sql))
        {
          $zbase['_bases'][$bid]['_masks'] = $conn->fetch_assoc($rs);
          $conn->free_result($rs);
        }
      }
      if (isset($zbase['_bases'][$bid]['_masks']))
      {
        $masks = $zbase['_bases'][$bid]['_masks'];
        $sql = 'SELECT xml, REVERSE(BIN(status)) AS bstat FROM record WHERE ((status ^ ' . $masks['mask_xor'] . ') & ' . $masks['mask_and'] . ')=0 AND record_id="' . $connbas->escape_string($rid) . '"';
        if (($rsRec = $connbas->query($sql)))
        {
          $row = $connbas->fetch_assoc($rsRec);
          $xml = $row['xml'];
          $bstat = $row['bstat'];
          $connbas->free_result($rsRec);

          $lstokcount++;
        }
      }

      // on decoupe le status binaire (inverse)
      $_lst[$idia]['statbits'] = array();
      if (isset($user->_rights_bas[$basrec['bid']]) && $user->_rights_bas[$basrec['bid']]['chgstatus'])
      {
        foreach ($_tstatbits as $n => $s)
        {
          $_lst[$idia]['statbits'][$n]['value'] = (substr($bstat, $n, 1) == '1') ? '1' : '0';
          $_lst[$idia]['statbits'][$n]['dirty'] = false;
        }
      }

      $_lst[$idia]['fields'] = array();
      $_lst[$idia]['originalname'] = '';
      if ($xml != '' && ($sxe = simplexml_load_string($xml)))
      {
        foreach ($sxe->doc as $docnode) // plusieurs docs ??? (tres vieux code)
        {
          $_lst[$idia]['originalname'] = basename($docnode["originalname"]);
          break; // on ne prend que le premier de toutes facons
        }

        $z = $sxe->xpath('/record/description');
        if ($z && is_array($z))
        {
          foreach ($z[0] as $ki => $vi)
          {

            if (!isset($_tfields[$ki]))
              continue; // champ inconnu dans la structure

              $k = $_tfields[$ki]['_idx'];

            if (!isset($_lst[$idia]['fields'][$k]))
              $_lst[$idia]['fields'][$k] = array('dirty' => false, 'value' => '');

            if ($_tfields[$ki]['multi'])
            {
              $vi = trim(str_replace(array("\r", "\n", "\t"), array(' ', ' ', ' '), $vi)); // caracteres interdits dans un mval
              if ($vi == '')
                continue; // dans le multi-value, on supprime les mots vides
              if (!is_array($_lst[$idia]['fields'][$k]['value']))
                $_lst[$idia]['fields'][$k]['value'] = array();
              $_lst[$idia]['fields'][$k]['value'][] = (string) $vi;
            }
            else
              $_lst[$idia]['fields'][$k]['value'] = (string) $vi;
          }
        }
      }

      $_lst[$idia]['subdefs'] = array('thumbnail' => null, 'preview' => null);

      $thumb = answer::getThumbnail($ses_id, $bid, $rid, true);


      $_lst[$idia]['subdefs']['thumbnail'] = array('url' => $thumb['thumbnail']
          , 'w' => $thumb['w']
          , 'h' => $thumb['h']);

      $_lst[$idia]['preview'] = answer::get_preview($bid, $rid, false);
      $_lst[$idia]['subdefs']['preview'] = $thumb['preview'];
      $_lst[$idia]['type'] = $thumb['type'];
    }
  }
?>
  p4.edit.T_records = <?php echo p4string::jsonencode($_lst); ?>;
  p4.edit.T_sgval = <?php echo p4string::jsonencode($T_sgval); ?>;
  p4.edit.T_id  = p4.edit.T_pos = <?php echo p4string::jsonencode(array_keys($_lst)); ?>;
  p4.edit.T_mval = [];

  </script>

<?php
}
?>
<script type="text/javascript">

<?php
if ($editreg != null)
{

  if ((isset($usrRight) && isset($usrRight[$editreg["coll_idLoc"]]) && $usrRight[$editreg["coll_idLoc"]] == "1"))
  {

    $canEditchu = true;
  }
  else
  {
    $editreg = null;
  }
}

$html = "";
$alertMsg = "";

if ($nb_rec_cant_edit > 0)
{
  if ($nb_rec_cant_edit == 1 && $nb_rec_can_edit == 0)
  {
    $alertMsg .= _('prod::editing: Vos droits sont insuffisants pour editer ce document') . "\n";
  }
  elseif ($nb_rec_cant_edit == 1)
  {
    $alertMsg .= _('prod::editing: 1 document ne peut etre edite car vos droits sont induffisants') . "\n";
  }
  else
  {
    $alertMsg .= sprintf(_('prod::editing: %d documents ne peuvent etre edites car vos droits sont induffisants'), $nb_rec_cant_edit) . "\n";
  }
}


if ($lstokcount == 0)
{
  $html .= "<div style='text-align:center;margin-top:10%'>";
//				$alertMsg .= _('prod::editing: Aucun document ne peut editer, droits insuffisants');
//				$html .= '<div>'.$alertMsg.'</div>';
  $html .= "<input type='button' value='" . _('boutton::fermer') . "' style='position:relative; bottom:0px;' class='input-button' onclick=\"edit_cancelMultiDesc(event);\" />\r\n";

  $html .= "</div";
}
else
{
  if (($e = (count($_lst) - $lstokcount)) > 0)
  {
    if ($e == 1)
    {
      $alertMsg .= _('prod::editing: le document a ete supprime de la base, aucune description a editer') . "\n";
    }
    else
    {
      $alertMsg .= _('prod::editing: Les documents ont ete supprime de la base, rien a editer') . "\n";
    }
  }

  $i = 0;

  $html .= "<div id='EDIT_ALL' style='white-space:normal; position:absolute; top:0px; left:0px; bottom:0;right:0'>\r\n";

  ########################
  #  POUR LE FAKE FOCUS
  ########################
  $html .= "	<div style=\"display:none;\">\r\n";
  $html .= "		<form onsubmit=\"return(false)\" ><input style=\"font-size:2px; width:5px;\" type=\"text\" id=\"editFakefocus\" /></form>\r\n";
  $html .= "	</div>\r\n";

  ########################################
  // DIV DU TRAIN --> FILM
  ########################################

  $topbox = user::getPrefs('editing_top_box');

  $html .= "	<div id='EDIT_TOP' style='position:absolute; height:" . $topbox . ";'>\r\n";
  $html .= "	<div id='EDIT_MENU' style='position:absolute; top:0px; left:0px; width:100%; height:20px; overflow:hidden;'>\r\n";
  $html .= "		<div id=\"EDIT_ZOOMSLIDER\" ></div>\r\n\r\n";
  $html .= "	</div>\r\n";

  if ($editwhat == 'GRP')
  {
    $i = 0;
    $html .= "<div class='GRP_IMAGE_REP' style='padding:5px;margin:5px;position:absolute; top:" . $i . "px; left:" . $i . "px; width:136px; height:136px'><div id=\"EDIT_GRPDIAPO\" style='position:absolute;'>";
    $html .= formatDiapo(current(array_keys($_lst)), "margin:0;position:absolute; top:" . $i . "px; left:" . $i . "px; width:134px; height:134px");
    $html .= "</div></div>";
    $html .= "<div id=\"EDIT_FILM2\" style=\"left:160px;\">\r\n";
    HTML_Train($html, true);
    $html .= "</div>";
  }
  elseif ($editwhat == 'SSEL')
  {
    $html .= "<div id=\"EDIT_FILM2\" class='ui-corner-all'>\r\n";
    HTML_Train($html, false);  // dessine le train de toutes imagettes
    $html .= "</div> ";
  }
  elseif ($editwhat == 'LST')
  {
    $html .= "<div id=\"EDIT_FILM2\" class='ui-corner-all'>\r\n";
    HTML_Train($html, false);  // dessine le train de toutes imagettes
    $html .= "</div>";
  }

  $html .= "	</div>"; // fin EDIT_TOP
  // horiz slider
//				$html .= "	<div id='EDIT_HSPLIT' class='gui_hsplitter' style='position:absolute; top:".$topbox.";z-index:9999'></div>\r\n\r\n";
  ########################################
  // EDIT_MID : bloc horizontal central
  ########################################
  $html .= "	<div id='EDIT_MID' style='position:absolute; left:0px; width:100%; bottom:32px; overflow:hidden; border:none;'>\r\n";

  ########################################
  // bloc de la liste de champs et de la saisie
  ########################################
  $html .= "		<div id='EDIT_MID_L' class='ui-corner-all'>\r\n";

  ########################################
  // DIV DE LA LISTE DES CHAMPS
  ########################################

  $rightbox = user::getPrefs('editing_right_box');

  $html .= "<div id=\"divS_wrapper\" style=\"width:" . $rightbox . "\"><div id=\"divS\">\r\n";
  HTML_FieldsList($html); // dessine la liste des champs
  $html .= "			</div></div> <!-- FIN divS --> \r\n\r\n";

  ########################################
  // s�paration liste champs / saisie
  ########################################
//				$html .= "			<div id='EDIT_VSPLIT' class='gui_vsplitter' style='left:".$rightbox.";z-index:9999;'></div>\r\n\r\n";
  ########################################
  // ZONE DE SAISIE
  ########################################
  // ffox : si on ne met pas overflow:auto, plus de curseur dans le textarea ????
  $html .= "			<div id=\"idEditZone\">\n";
  ########################################
  // NOM DU CHAMP
  $html .= "				<div style='position:absolute; xtext-align:center; height:32px; width:100%;'>\n";
  HTML_EditZone_Name($html);
  $html .= "				</div>\r\n";
  ########################################
  ########################################
  // SAISIE (mono ou multi)
  $html .= "				<div id=\"EDIT_EDIT\" style='position:absolute; top:32px; width:100%; bottom:32px; overflow-x:hidden;overflow-y:auto;'>\n";
  HTML_EditZone_Edit($html);
  $html .= "				</div>\r\n";
  $html .= '<div id="idDivButtons" style="position:absolute;bottom:0;left:0;right:0;height:22px;display:none;text-align:center;">
                                                                <input id="ok"     type="button" value="' . _('boutton::remplacer') . '"     class="input-button" onclick="edit_validField(event, \'ok\');return(false);">
                                                                <input id="fusion" type="button" value="' . _('boutton::ajouter') . '"    class="input-button" onclick="edit_validField(event, \'fusion\');return(false);">
                                                                <input id="cancel" type="button" value="' . _('boutton::annuler') . '" class="input-button" onclick="edit_validField(event, \'cancel\');return(false);">
                                                </div>';
  ########################################

  $html .= "			</div>\r\n"; // fin de idEditZone
  ########################################


  $html .= "		</div>\r\n"; // fin de EDIT_MID_L
  ########################################
  ########################################
  // s�paration liste EDIT_MID_L / thesaurus
  ########################################
  $leftbox = user::getPrefs('editing_left_box');

//				$html .= "		<div id='EDIT_VSPLIT2' class='gui_vsplitter gui_vsplitter2' style='position:absolute;  right:".$leftbox.";z-index:9999;'></div>\r\n\r\n";
  ########################################
  // ZONE DE THESAURUS
  ########################################
  $html .= "		<div id=\"EDIT_MID_R\" style=\"width:" . $leftbox . "\">\r\n";
  HTML_Thesaurus($html);
  $html .= "		</div>\r\n";


  $html .= "	</div> <!-- FIN EDIT_MID -->\r\n"; // fin de EDIT_MID
  ########################################
  ########################################
  // BOUTONS DE VALIDATION D'EDITING
  ########################################
  $html .= "	<div id=\"buttonEditing\" style=\"text-align:center; margin:0px; padding:0px; overflow:hidden; position:absolute; bottom:0px; left:0px; width:100%; height:22px;\">\r\n";
  $html .= "		<input type='button' value='" . _('boutton::valider') . "' class='input-button' onclick=\"edit_applyMultiDesc(event);\" />\r\n";
  $html .= "               \r\n";
  $html .= "		<input type='button' value='" . _('boutton::annuler') . "' class='input-button' onclick=\"edit_cancelMultiDesc(event);\" />\r\n";
  $html .= "	</div>\r\n";

  $html .= "</div> <!-- FIN DE EDIT_ALL -->\r\n";
}
if ($alertMsg != "")
  printf("alert(\"%s\");", p4string::MakeString($alertMsg, 'js', '"'));

$html .= "\r\n";
$html .= "<div id=\"EDIT_WORKING\" style=\"position:absolute;top:100px;left:1px;width:100%;display:none\">\r\n";
$html .= "	<center>\r\n";
$html .= "		<br/><br/><br/><br><b><h4>" . _('prod::editing:indexation en cours') . "</h4></b>\r\n";
$html .= "		<span id='saveeditPbarI'></span> / <span id='saveeditPbarN'></span>\r\n";
$html .= "		<br/><br/><br/>";
$html .= "		<input type='button' class='input-button' value=\"" . _('boutton::fermer') . "\" onClick=\"$('#EDITWINDOW').fadeOut();hideOverlay(2);return(false);\" />";
$html .= "	</center>\r\n";
$html .= "</div>\r\n";

$html .= "<div id=\"EDIT_CLOSEDIALOG\" style=\"display:none;\" title=\"" . _('boutton::fermer') . " \">\r\n";
$html .= _('prod::editing: valider ou annuler les modifications') . "\r\n";
$html .= "</div>\r\n";
$html .= "";

$html .= '<div style="display:none" id="Edit_copyPreset_dlg">';
$html .= '	<form onsubmit="return false;">';
$html .= '		' . _('edit::preset:: titre') . ' : <input class="EDIT_presetTitle" type="text" name="name" style="width:300px;">';
$html .= '		<div style="position:relative; top:0px; left:0px; width:550px; height:250px; overflow:auto;">';
$html .= '		</div>';
$html .= '	</form>';
$html .= '</div>';
?>
</script>
<?php echo $html; ?>
<script type="text/javascript">
  startThisEditing(<?php echo (int) $same_sbas_id; ?>,"<?php echo $editwhat; ?>","<?php echo $regbasprid; ?>",<?php echo is_null((int) $parm["ssel"]) ? false : (int) $parm['ssel']; ?>);
</script>


<?php

function HTML_Thesaurus(&$html)
{
  global $zbase, $same_sbas_id, $_tfields;
  global $hasThesaurus;

  $session = session::getInstance();
  ob_start(null, 0);
?>	
  <div style='position:absolute; top:0; left:0; right:0; bottom:0;' class='tabs'>

    <ul>
    <?php
    if ($hasThesaurus)
    {
    ?>
      <li><a href="#TH_Ofull"><?php echo _('phraseanet:: thesaurus') ?></a></li>
    <?php
    }
    ?>
    <li><a href="#TH_Oclipboard"><?php echo _('phraseanet:: presse-papier') ?></a></li>
    <li><a href="#TH_Opreview"><?php echo _('phraseanet:: preview') ?></a></li>
    <li><a href="#TH_Oreplace"><?php echo _('prod::editing: rechercher-remplacer') ?></a></li>
    <li><a href="#TH_Opresets"><?php echo _('prod::editing: modeles de fiches') ?></a></li>
  </ul>

  <?php
    if ($hasThesaurus)
    {
  ?>
      <div id='TH_Ofull'>
        <div class='thesaurus' ondblclick='return(edit_dblclickThesaurus(event));' onclick='return(edit_clickThesaurus(event));'>
          <p id='TH_T.<?php echo $same_sbas_id ?>.T'>
            <u id='TH_P.<?php echo $same_sbas_id ?>.T'>+</u><a id='GL_W.<?php echo $same_sbas_id ?>.T' style='FONT-WEIGHT: bold;'><?php echo phrasea::sbas_names($same_sbas_id) ?></a>
          </p>
          <div id='TH_K.<?php echo $same_sbas_id ?>.T' class='c'><?php echo _('phraseanet::chargement') ?></div>
        </div>
        <img style="position:absolute; margin:auto" id="TH_searching" src="/skins/icons/ftp-loader-blank.gif" />
      </div>
  <?php
    }
  ?>

    <div id='TH_Oclipboard'>
      <div class="PNB10">
        <textarea id='CLIP_CC' style="width:100%;height:100% !important;height:300px;"></textarea>
      </div>
    </div>
    <div id='TH_Opreview'>
      <div class="PNB10">
      </div>
    </div>
    <div id='TH_Oreplace'>
      <table style="position:relative; left:0px; width:100%;">
        <tr>
          <td width="100"><?php echo _('prod::editing::replace: remplacer dans le champ') ?></td>
          <td>
            <select id="EditSRField">
              <option value=""><?php echo _('prod::editing::replace: remplacer dans tous les champs') ?></option>
            <?php
            foreach ($_tfields as $fn => $f)
              echo '<option value="', $f['_idx'], '">', p4string::MakeString($fn, 'html', '"'), '</option>';
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td valign="top"><?php echo _('prod::editing:replace: chaine a rechercher') ?></td>
        <td><textarea id="EditSearch" style="width:100%; height:45px; font-size:14px;"></textarea></td>
      </tr>
      <tr>
        <td valign="top"><?php echo _('prod::editing:remplace: chaine remplacante') ?></td>
        <td><textarea id="EditReplace" style="width:100%; height:45px; font-size:14px;"></textarea></td>
      </tr>
      <tr>
        <td valign="top"><?php echo _('prod::editing:remplace: options de remplacement') ?></td>
        <td>
          <input type="checkbox" class="checkbox" id="EditSROptionRX" value="regexp" onchange="changeReplaceMode(this);">
          <?php
            echo _('prod::editing:remplace::option : utiliser une expression reguliere');
            switch ($session->usr_i18n)
            {
              case 'en':
              default:
                $help_link = 'https://secure.wikimedia.org/wikipedia/en/wiki/Regular_expression';
                break;
              case 'de':
                $help_link = 'https://secure.wikimedia.org/wikipedia/de/wiki/Regul%C3%A4rer_Ausdruck';
                break;
              case 'fr':
                $help_link = 'https://secure.wikimedia.org/wikipedia/fr/wiki/Expression_rationnelle';
                break;
              case 'ar':
                $help_link = 'https://secure.wikimedia.org/wikipedia/ar/wiki/%D8%AA%D8%B9%D8%A7%D8%A8%D9%8A%D8%B1_%D9%86%D9%85%D8%B7%D9%8A%D8%A9';
                break;
            }
          ?>
            <a href="<?php echo $help_link; ?>" target="_blank"><img src="/skins/icons/help.png" title="<?php echo _('Aide sur les expressions regulieres') ?>"/></a>
            <br/>
            <br/>
            <div id="EditSR_RX" style="display:none">
              <input type="checkbox" class="checkbox" id="EditSR_RXG"> <?php echo _('prod::editing:remplace::option: remplacer toutes les occurences') ?>
              <br/>
              <input type="checkbox" class="checkbox" id="EditSR_RXI"> <?php echo _('prod::editing:remplace::option: rester insensible a la casse') ?>
            </div>
            <div id="EditSR_TX" style="display:block">
              <input type="radio" class="checkbox" name="EditSR_Where" value="exact"> <?php echo _('prod::editing:remplace::option la valeur du cahmp doit etre exacte') ?>
              <br />
              <input type="radio" class="checkbox" name="EditSR_Where" value="inside" checked> <?php echo _('prod::editing:remplace::option la valeur est comprise dans le champ') ?>
              <br />
              <input type="checkbox" class="checkbox" id="EditSR_case"> <?php echo _('prod::editing:remplace::option respecter la casse') ?>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="2" align="center">
            <br />
            <br />
            <input type="button" class='input-button' value="<?php echo _('boutton::valider') ?>" onclick="replace(); return(false);" style="position:relative; margin:auto" />
          </td>
        </tr>
      </table>
      <!--			</div>-->
    </div>

    <div id='TH_Opresets'>
      <a href="javascript:void();" onclick="preset_copy();return(false);"><?php echo _('boutton::ajouter') ?></a>
      <UL style="position:absolute; top:5px; left:0px; bottom:0px; right:0px; overflow:auto" class="EDIT_presets_list">
      </UL>
    </div>
  </div>
<?php
            $html .= ob_get_clean();
          }

// ---------------------------------------------------------
// dessine une diapo
// ---------------------------------------------------------
          function formatDiapo($idia, $diapostyle)
          {
            ob_start(null, 0);

            global $ses_id, $usr_id, $lng, $user;
            global $_lst;
            global $zbase;

            $basrec = $_lst[$idia];

            $bid = $basrec['bid'];
            $rid = $basrec['rid'];
            $cid = $zbase['_bases'][$bid]['coll_id'];

            if ($basrec['subdefs']['thumbnail'])
            {
              $thumbnail = $basrec['subdefs']['thumbnail']['url'] . '?u=' . mt_rand(1, 1000000);
              $r = 80;
              if (($w = $basrec['subdefs']['thumbnail']['w']) > ($h = $basrec['subdefs']['thumbnail']['h']))
              {
                // horizontale, centrer verticalement
                // $tp = (int)(50 * (1-( ($r/100) * $h/$w)) );
                $tp = 50 - (int) ( ($r / 2) * $h / $w );
                $left = 50 - (int) ( ($r * $h / (2 * $w)) * $w / $h );
                $style = "position:absolute; top:" . $tp . "%; width:" . $r . "%; height:auto;left:" . $left . "%; ";
              }
              else
              {
                $tp = (int) ((100 - $r) / 2);
                $left = (int) ((100 - $r * $w / ($h)) / 2);
                $style = "position:absolute; top:" . $tp . "%; height:" . $r . "%; width:auto;left:" . $left . "%;";
              }
            }

            $class_status = 'nostatus';
            if (isset($user->_rights_bas[$basrec['bid']]) && $user->_rights_bas[$basrec['bid']]['chgstatus'])
            {
              $class_status = '';
            }
?>
            <div pos="<?php echo $idia ?>" id="idEditDiapo_<?php echo $idia ?>" class="diapo <?php echo $class_status; ?> <?php echo $basrec['type'] ?>_bloc" style="<?php echo $diapostyle ?>;">
              <div class='titre'>
    <?php echo p4string::MakeString($basrec['originalname'], 'html') . "\n" ?>
          </div>
          <img class="edit_IMGT" id="idEditDiapoImg_<?php echo $idia ?>" style="<?php echo $style ?>" onclick="edit_clk_editimg(event, <?php echo $idia ?>);" src="<?php echo $thumbnail ?>" />
          <div style='position:absolute; top:0px; left:0px; height:20px'>
            <img class="require_alert" src="/skins/icons/alert.png" style="display:none;cursor:help;" title="<?php echo _('edit::Certains champs doivent etre remplis pour valider cet editing'); ?>">
          </div>
          <div style='position:absolute; bottom:0px; left:0px; height:20px'>
    <?php
            $preview_rollover = answer::get_preview_rollover($bid, $rid, $ses_id, true, $usr_id, $basrec['subdefs']['preview'], $basrec['type']);
            if (trim($preview_rollover) != "")
            {
              print("					" . "<img title='" . $preview_rollover . "' class=\"previewTips\" src=\"/skins/icons/zoom.gif\"> " . "\n");
            }
    ?>
          </div>
          <div class="reg_opts" style="display:none;position:absolute;bottom:0;right:0;">
            <a style="float:right;padding:0;margin:0;cursor:pointer;" class="contextMenuTrigger" id="editContextTrigger_<?php echo $bid ?>_<?php echo $rid ?>">&#9660;</a>
            <table cellspacing="0" cellpadding="0" style="display:none;" id="editContext_<?php echo $bid ?>_<?php echo $rid ?>" class="contextMenu editcontextmenu">
              <tbody>
                <tr>
                  <td>
                    <div class="context-menu context-menu-theme-vista">
                      <div title="" class="context-menu-item">
                        <div class="context-menu-item-inner" onclick="setRegDefault('<?php echo $idia ?>','<?php echo $rid ?>');"><?php echo _('edit: chosiir limage du regroupement') ?></div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="editDiaButtons" style="position:absolute; bottom:0px; right:0px; width:30px; height:12px; display:none">
            <img id="idEditDiaButtonsP_<?php echo $idia ?>" onclick="edit_diabutton(<?php echo $idia ?>, 'add');" style="cursor:pointer" src="/skins/icons/tri_plus.gif"/>
            <img id="idEditDiaButtonsM_<?php echo $idia ?>" onclick="edit_diabutton(<?php echo $idia ?>, 'del');" style="cursor:pointer" src="/skins/icons/tri_minus.gif"/>
          </div>
        </div>
<?php
            return(ob_get_clean());
          }

// ---------------------------------------------------------
// dessine le train des imagettes
// ---------------------------------------------------------
          function HTML_Train(&$html, $isGrp)
          {
            global $_lst;

            $i = 0;
            foreach ($_lst as $basrec)
            {
              if ($isGrp && $i == 0)  // dans un grpmt, la premiere diapo (grp) est deja en haut a gauche
              {
                $i++;
                continue;
              }
              $html .= formatDiapo($i, 'width:' . user::getPrefs('editing_images_size') . 'px; height:' . user::getPrefs('editing_images_size') . 'px;');
              $i++;
            }
          }

// ---------------------------------------------------------
// dessine le 'formulaire' des champs
// ---------------------------------------------------------
          function HTML_FieldsList(&$html)
          {
            ob_start(null, 0);

            global $_tfields;
            global $lng;
            global $cssfile;
?>
            <div class="edit_field" id="EditFieldBox_status" onMouseDown="return(edit_mdwn_status(event));" >
  <?php echo _('prod::editing::fields: status ') ?>
          </div>
<?php
            foreach ($_tfields as $fieldname => $f)
            {
              $i = $f['_idx'];
?>
              <div class="edit_field" id="EditFieldBox_<?php echo $i ?>" onclick="return(edit_mdwn_fld(event, <?php echo $i ?>, '<?php echo $f['name'] ?>'));" >
                <img id="editSGtri_<?php echo $i ?>" style="visibility:hidden;" src="/skins/prod/<?php echo $cssfile ?>/images/suggested.gif" />
                <span id="spanidEditFieldBox_<?php echo $i ?>">
    <?php echo $f['name'] ?> <?php echo $f['required'] ? '<span style="font-weight:bold;font-size:16px;"> * </span>' : ''; ?>:
            </span>
            <span class="fieldvalue" id="idEditField_<?php echo $i ?>" >
                      					???
            </span>
          </div>
<?php
            }

            $html .= ob_get_clean();
          }

// ---------------------------------------------------------
// dessine le nom du champ dans la zone de saisie
// ---------------------------------------------------------
          function HTML_EditZone_Name(&$html)
          {
            ob_start(null, 0);
?>
            <div id="idExplain" style="color:#ff0000;"></div>
            <center>
              <table style='position:relative; top:-15px; table-layout:fixed; width:240px'>
                <tr>
                  <td style='width:30px; text-align:right'>
                    <input type='button' value='&#9668;' class='input-button' onclick="edit_chgFld(event, -1);return(false);" />
                  </td>
                  <td id="idFieldNameEdit" style='text-align:center; width:80px; overflow:hidden'></td>
                  <td style='width:30px; text-align:left'>
                    <input type='button' value='&#9658;' class='input-button' onclick="edit_chgFld(event, 1);return(false);" />
                  </td>
                </tr>
              </table>
            </center>
<?php
            $html .= ob_get_clean();
          }

// ---------------------------------------------------------
// dessine la zone de saisie
// ---------------------------------------------------------
          function HTML_EditZone_Edit(&$html)
          {
            ob_start(null, 0);

            global $_tstatbits;
?>
            <div id="ZTextMonoValued">
              <textarea id="idEditZTextArea" style="font-size:15px;height:99%;left:0;margin:0;padding:0;position:absolute;top:0;width:99%;" onmousedown="return(edit_mdwn_ta(event));" onmouseup="return(edit_mup_ta(event, this));" onkeyup="return(edit_kup_ta(event, this));" onKeyDown="return(edit_kdwn(event, this));"></textarea>
              <div id="idEditDateZone"style="position:absolute; top:30px; left:0px; display:none">
              </div>
            </div>

            <div id="ZTextMultiValued" style="position:absolute; top:0px; left:0px; width:100%; height:100%; display:none;">
              <form onsubmit="edit_addmval();return(false);" style="position:absolute; height:30px; left:2px; right:2px;">
                <div style="position:absolute; top:0px; left:0px; right:70px; height:17px;">
                  <input type='text' style="font-size:15px; position:absolute; top:0px; left:0px; width:100%; height:100%;" id="EditTextMultiValued" onkeyup="reveal_mval();" value="" />
                </div>
                <div style="position:absolute; top:6px; width:60px; right:0px; height:11px;">
                  <img id="EditButAddMultiValued" style="cursor:pointer" src="/skins/icons/tri_plus.gif"  onclick="edit_addmval();" />

                  <img id="EditButDelMultiValued" style="cursor:pointer" src="/skins/icons/tri_minus.gif" onclick="edit_delmval();" />
                </div>
              </form>
              <div id="ZTextMultiValued_values" style="position:absolute; top:22px; left:4px; right:4px; bottom:4px; overflow:scroll;">
              </div>
            </div>

            <div id="ZTextStatus" style="position:absolute; top:0px; left:0px; width:100%; height:100%; display:none;">
              <div class="nostatus">
    <?php echo _('Aucun statut editable'); ?>
          </div>
          <div class="somestatus">
    <?php echo _('Les status de certains documents ne sont pas accessible par manque de droits'); ?>
          </div>
          <div class="displaystatus">
            <table>
      <?php
            foreach ($_tstatbits as $n => $s)
            {
      ?>
              <tr>
                <td style="padding-left:10px">
                  <span style="cursor:pointer" onclick="edit_clkstatus(event, <?php echo $n ?>, 0);">
                    <div id="idCheckboxStatbit0_<?php echo $n ?>" class="gui_ckbox_0"></div>
            <?php
              if ($s['img_off'])
                echo '<img src="' . $s['img_off'] . '" title="' . $s['label0'] . '" style="width:16px;height:16px;vertical-align:bottom" /> ';
              echo p4string::MakeString(trim($s['label0']), 'html') . "\n";
            ?>
            </span>
          </td>
          <td style="padding-left:20px">
            <span style="cursor:pointer" onclick="edit_clkstatus(event, <?php echo $n ?>, 1);">
              <div id="idCheckboxStatbit1_<?php echo $n ?>"  class="gui_ckbox_0"></div>
            <?php
              if ($s['img_on'])
                echo '<img src="' . $s['img_on'] . '" title="' . $s['label1'] . '" style="width:16px;height:16px;vertical-align:bottom" /> ';
              echo p4string::MakeString(trim($s['label1']), 'html') . "\n";
            ?>
            </span>
          </td>
        </tr>
      <?php
            }
      ?>
          </table>
        </div>
      </div>

<?php
            $html .= ob_get_clean();
          }
?>
