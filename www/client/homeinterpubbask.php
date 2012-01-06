<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();

$th_size = $user->getPrefs('images_size');

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
$ACL = $user->ACL();

$RN = array("\r\n", "\n", "\r");

$conn = $appbox->get_connection();

$sql = 'SELECT s.public, s.pub_restrict,c.sselcont_id, c.base_id, sb.*, c.record_id, s.ssel_id, s.name, s.descript, c.ord, ' .
        'DATE_FORMAT(s.pub_date,"' . _('phraseanet::technique::datetime') . '") AS pub_date, ' .
        'DATE_FORMAT(s.updater,"' . _('phraseanet::technique::date') . '") AS updater, ' .
        's.updater as dateC1, s.pub_date as dateC2,' .
        ' n.id, u.usr_nom, u.usr_prenom, u.usr_login, u.usr_mail ' .
        ', bu.mask_and ' .
        ', bu.mask_xor ' .
        'FROM (sselcont c, ssel s, usr u, bas b, sbas sb) ' .
        'LEFT JOIN sselnew n ' .
        'ON (n.ssel_id = s.ssel_id AND n.usr_id = :usr_id) ' .
        ' LEFT JOIN basusr bu ON (bu.base_id = b.base_id  AND bu.usr_id = :usr_id_bis)' .
        'WHERE s.ssel_id = c.ssel_id AND s.public="1" ' .
        'AND (s.pub_restrict="0" ' .
        'OR (s.pub_restrict="1" AND c.base_id IN ' .
        ' (SELECT base_id FROM basusr WHERE usr_id = :usr_id_sub AND actif = "1")))' .
        ' AND u.usr_id = s.usr_id AND temporaryType=0 ' .
        ' AND b.sbas_id = sb.sbas_id' .
        ' AND b.base_id = c.base_id' .
        ' ORDER BY s.pub_date DESC, c.ord ASC';

$sqlMe = 'SELECT usr_login, usr_password FROM usr WHERE usr_id = :usr_id';

$stmt = $conn->prepare($sqlMe);
$stmt->execute(array(':usr_id' => $session->get_usr_id()));
$rawMe = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$info_usr = null;

if ($rawMe)
{
  $info_usr = $rawMe;
}

$core = \bootstrap::getCore();
$twig = $core->getTwig();

$stmt = $conn->prepare($sql);
$usr_id = $session->get_usr_id();
$stmt->execute(array(':usr_id' => $usr_id, ':usr_id_bis' => $usr_id, ':usr_id_sub' => $usr_id));
$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

$sselid = null;
$o = 0;
$out = '';

$feed = '';
$feed .= '<div style="height:50px;" class="homePubTitleBox">' .
        '<div style="float:left;width:350px;"><h1 style="font-size:20px;margin-top:15px;">' . _('publications:: dernieres publications') . '</h1></div>' .
        '<div style="float:right;width:160px;text-align:right;cursor:pointer;" class="subscribe_my_rss">
                    <h1 style="font-size:17px;margin-top:19px;"> ' .
        _('publications:: s\'abonner aux publications') . ' ' .
        '<img style="border:none;" src="/skins/icons/rss16.png" />
                    </h1>' .
        '</div></div>';

foreach ($rs as $row)
{
  if ($row['ssel_id'] != $sselid)
  {
    if ($sselid !== null)
    {



      $item .= '<div style="width:100%;position:relative;float:left;" id="PUBLICONT' . $sselid . '">' . $out;
      $item .= '</div>' .
              '</div></div>';

      if ($itemIsOk)
        $feed .= $item;
    }

    $itemIsOk = false;

    $sselid = $row['ssel_id'];
    $ord = $row['ord'];
    $o = 0;
    $out = '';
    $neverSeen = '';
    $publisher = $row['usr_prenom'] . ' ' . $row['usr_nom'];
    if ($publisher == ' ')
      $publisher = $row['usr_mail'];
    if ($publisher == '')
      $publisher = 'Unreferenced user';

    if ($row['id'] != '')
      $neverSeen = _('publications:: publication non lue');

    $item = '';
    $item .= '<div class="boxPubli">' .
            '<div class="titlePubli">' .
            '<h2 class="htitlePubli">' .
            '<a class="homePubTitle" onclick="openCompare(\'' . $sselid . '\');">' . $row['name'] .
            '</a> <span style="font-size:12px;color:red;">' . $neverSeen . '</span></h2>' .
            '<span class="publiInfos">' .
            ' ' . $row['pub_date'] .
            '  ';

    if ($row['usr_mail'] != '')
      $item .= '<a class="homePubLink" href="mailto:' . $row['usr_mail'] . '">';

    $item .= $publisher;

    if ($row['usr_mail'] != '')
      $item .= '</a>';

    if ($row['dateC1'] != $row['dateC2'])
      $item .= '<br/><span style="font-style:italic;">' . _('publications:: derniere mise a jour') . ' ' . $row['updater'] . '</span><br/><br/>';

    $item .= '</span></div><div class="descPubli"><div style="margin:10px 0 10px 20px;width:80%;">';


    if (trim(str_replace($RN, '', $row['descript'])) != '')
    {
      $row['descript'] = str_replace($RN, '<br/>', $row['descript']);
      $item .= '' . $row['descript'];
    }
    $item .= '</div>';
  }





  $ord = $row['ord'];
  $statOk = true;
  if ($row['public'] == 1 && $row['pub_restrict'] == 1)
  {
    $statOk = false;

    try
    {
      $connsbas = connection::getPDOConnection($row['sbas_id']);

      $sql = 'SELECT record_id FROM record WHERE ((status ^ ' . $row['mask_xor'] . ') & ' . $row['mask_and'] . ')=0
                AND record_id = :record_id';
      $stmt = $connsbas->prepare($sql);
      $stmt->execute(array(':record_id' => $row['record_id']));
      $statOk = ($stmt->rowCount() > 0);
      $stmt->closeCursor();
    }
    catch (Exception $e)
    {
      
    }
  }


  $layoutmode = $user->getPrefs('view');

  if ($statOk)
  {
    $record = new record_adapter($row["sbas_id"], $row["record_id"]);
    $sbas_id = phrasea::sbasFromBas($row['base_id']);

    $captionXML = $record->get_xml();

    $thumbnail = $record->get_thumbnail();

    $title = $record->get_title();
    $exifinfos = $record->get_technical_infos();
    $caption = $twig->render('common/caption.html', array('view' => 'internal_publi', 'record' => $record));

    $o++;
    $itemIsOk = true;
    $bottom = 0;
    $right = 10;
    $left = 0;
    $top = 10;


    if (trim($preview) != '')
      $preview = "<div tooltipsrc='/prod/tooltip/preview/" . phrasea::sbasFromBas($row["base_id"]) . "/" . $row["record_id"] . "/' class=\"previewTips\"></div>&nbsp;";

    $docType = $record->get_type();
    $isVideo = ($docType == 'video');
    $isAudio = ($docType == 'audio');
    $isImage = ($docType == 'image');
    $isDocument = ($docType == 'document');

    $duration = '';

    if (!$isVideo && !$isAudio)
      $isImage = true;

    if ($isVideo)
    {
      $duration = $record->get_formated_duration();
      if ($duration != '')
        $duration = '<div class="duration">' . $duration . '</div>';
    }
    if ($isAudio)
    {
      $duration = $record->get_formated_duration();
      if ($duration != '')
        $duration = '<div class="duration">' . $duration . '</div>';
    }


    $ratio = $thumbnail->get_width() / $thumbnail->get_height();

    if ($ratio > 1)
    {
      $cw = min(((int) $th_size - 30), $thumbnail->get_width());
      $ch = $cw / $ratio;
      $pv = floor(($th_size - $ch) / 2);
      $ph = floor(($th_size - $cw) / 2);
      $imgStyle = 'xwidth:' . $cw . 'px;xpadding:' . $pv . 'px ' . $ph . 'px;';
    }
    else
    {
      $ch = min(((int) $th_size - 30), $thumbnail->get_height());
      $cw = $ch * $ratio;

      $pv = floor(($th_size - $ch) / 2);
      $ph = floor(($th_size - $cw) / 2);

      $imgStyle = 'xheight:' . $ch . 'px;xpadding:' . $pv . 'px ' . $ph . 'px;';
    }



    $ident = $row["base_id"] . "_" . $row["record_id"];


    $out .= "<div style='width:" . ($th_size + 30) . "px;' sbas=\"" . $row['sbas_id'] . "\" id='IMGT_" . $row['base_id'] . "_" . $row['record_id'] . "_PUB_" . $sselid . "' class='IMGT diapo' onclick=\"openPreview('BASK','" . $ord . "','" . $sselid . "');\">";

    $out .= '<div>';
    $out .= "<div class=\"title\" style=\"height:40px;\">";

    $out .= $title; //$data['title'];

    $out .= "</div>\n";

    $out .= '</div>';

    $out .= "<table class=\"thumb w160px h160px\" style=\"xheight:" . (int) $th_size . "px;\" cellspacing='0' cellpadding='0' valign='middle'>\n<tr><td>";

    $out .= $duration . "<img title=\"" . str_replace('"', '&quot;', $caption) . "\" class=\" captionTips\" src=\"" . $thumbnail->get_url() . "\" style=\"" . $imgStyle . "\" />";

    $out .= "</td></tr></table>";

    $out .= '<div style="height: 25px;position:relative;">';
    $out .= '<table class="bottom">';
    $out .= '<tr>';
    $out .= '<td>';

    $out .= "</td>\n";

    $out .= "<td style='text-align:right;' valign='bottom' nowrap>\n";

    $out .= $preview;

    $out .= "</td>";
    $out .= "</tr>";
    $out .= "</table>";
    $out .= "</div>";


    $out .= "</div>";
  }
}

if (isset($item))
{
  $item .= ' <br/><div style="width:100%;position:relative;float:left;" id="PUBLICONT' . $sselid . '">' . $out;
  $item .= '</div>' .
          '</div></div>';
  if ($itemIsOk)
    $feed .= $item;
}

echo '<div>' . $feed . '</div>';

$sql = 'DELETE FROM sselnew WHERE usr_id = :usr_id AND ssel_id IN (SELECT ssel_id FROM ssel WHERE public="1")';
$stmt = $conn->prepare($sql);
$stmt->execute(array(':usr_id' => $usr_id));
$stmt->closeCursor();
