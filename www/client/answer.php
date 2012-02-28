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

/* @var $Core \Alchemy\Phrasea\Core */
require_once __DIR__ . "/../../lib/bootstrap.php";

$Core= \bootstrap::getCore();
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();
$registry = $appbox->get_registry();
$user = $Core->getAuthenticatedUser();

if (!isset($parm))
{

  $request = http_request::getInstance();
  $parm = $request->get_parms("mod", "bas"
          , "pag"
          , "qry", "search_type", "recordtype"
          , "qryAdv", 'opAdv', 'status', 'datemin', 'datemax'
          , 'dateminfield', 'datemaxfield'
          , 'datefield'
          , 'sort'
          , 'stemme'
          , 'infield'
          , "nba"
          , "regroup" // si rech par doc, regroup ,ou pizza
          , "ord"
  );
}
$qry = '';

if (trim($parm['qry']) != '')
{
  $qry .= trim($parm['qry']);
}
if (count($parm['opAdv']) > 0 && count($parm['opAdv']) == count($parm['qryAdv']))
{
  foreach ($parm['opAdv'] as $opId => $op)
  {
    if (trim($parm['qryAdv'][$opId]) != '')
    {
      if ($qry == trim($parm['qry']))
        $qry = '(' . trim($parm['qry']) . ')';
      $qry .= ' ' . $op . ' (' . trim($parm['qryAdv'][$opId]) . ')';
    }
  }
}
if ($qry == '')
  $qry = 'all';

$parm['qry'] = $qry;

$qrySbas = array();
if (is_null($parm['bas']))
{
  echo 'vous devez selectionner des collections dans lesquelles chercher';

  return;
}

if (!$parm["mod"])
  $parm["mod"] = "3X6";


$mod = explode("X", $parm["mod"]);
if (count($mod) == 1)
{
  $mod_row = (int) ($mod[0]);
  $mod_col = 1;
}
else
{
  $mod_row = (int) ($mod[0]);
  $mod_col = (int) ($mod[1]);
}
$mod_xy = $mod_col * $mod_row;

$tbases = array();




$options = new searchEngine_options();


$options->set_bases($parm['bas'], $user->ACL());
if (!is_array($parm['infield']))
  $parm['infield'] = array();

foreach($parm['infield'] as $offset=>$value)
{
  if(trim($value) === '')
    unset($parm['infield'][$offset]);
}

$options->set_fields($parm['infield']);
if (!is_array($parm['status']))
  $parm['status'] = array();
$options->set_status($parm['status']);
$options->set_search_type($parm['search_type']);
$options->set_record_type($parm['recordtype']);
$options->set_min_date($parm['datemin']);
$options->set_max_date($parm['datemax']);
$options->set_date_fields(explode('|', $parm['datefield']));
$options->set_sort($parm['sort'], $parm['ord']);
$options->set_use_stemming($parm['stemme']);

if ($parm['ord'] === NULL)
  $parm['ord'] = PHRASEA_ORDER_DESC;
else
  $parm['ord'] = (int) $parm['ord'];

$form = serialize($options);

$perPage = $mod_xy;

$search_engine = new searchEngine_adapter($registry);
$search_engine->set_options($options);


if ($parm['pag'] < 1)
{
  $search_engine->set_is_first_page(true);
  $search_engine->reset_cache();
  $parm['pag'] = 1;
}

$result = $search_engine->query_per_page($parm['qry'], (int) $parm["pag"], $perPage);



$proposals = $search_engine->is_first_page() ? $result->get_propositions() : false;

$npages = $result->get_total_pages();


$page = $result->get_current_page();

$ACL = $user->ACL();

if ($registry->get('GV_thesaurus'))
{
  ?>
  <script language="javascr<?php ?>ipt">
    document.getElementById('proposals').innerHTML = "<div style='height:0px; overflow:hidden'>\n<?php echo p4string::MakeString($qp['main']->proposals["QRY"], "JS") ?>\n</div>\n<?php echo p4string::MakeString(proposalsToHTML($qp['main']->proposals), "JS") ?>";
  <?php
  if ($registry->get('GV_clientAutoShowProposals'))
  {
    ?>
    if("<?php echo p4string::MakeString(proposalsToHTML($qp['main']->proposals), "JS") ?>" != "<div class=\"proposals\"></div>")
    chgOng(4);
    <?php
  }
  ?>
  </script>
  <?php
}



$history = queries::history();

echo '<script language="javascript" type="text/javascript">$("#history").empty().append("' . str_replace('"', '\"', $history) . '")</script>';

$nbanswers = $result->get_count_available_results();
$longueur = strlen($parm['qry']);

$qrys = '<div>' . _('client::answers: rapport de questions par bases') . '</div>';

foreach ($qrySbas as $sbas => $qryBas)
  $qrys .= '<div style="font-weight:bold;">' . phrasea::sbas_names($sbas) . '</div><div>' . $qryBas . '</div>';

$txt = "<b>" . substr($parm['qry'], 0, 36) . ($longueur > 36 ? "..." : "") . "</b>" . sprintf(_('client::answers: %d reponses'), (int) $nbanswers) . " <a style=\"float:none;display:inline-block;padding:2px 3px\" class=\"infoTips\" title=\"" . str_replace('"', "'", $qrys) . "\">&nbsp;</a>";
?>
<script type="text/javascript">
  $(document).ready(function(){
    p4.tot = <?php echo ($nbanswers > 0) ? $nbanswers : '0' ?>;
    document.getElementById("nb_answers").innerHTML = "<?php echo p4string::JSstring($txt) ?>";
  });
</script>
<?php
$npages = $result->get_total_pages();
$pages = '';
$ecart = 3;
$max = (2 * $ecart) + 3;

if ($npages > $max)
{
  for ($p = 1; $p < $npages; $p++)
  {
    if ($p == $page)
      $pages .= '<span class="naviButton sel">' . ($p) . '</span>';
    elseif (( $p >= ($page - $ecart) ) && ( ($p - 1) <= ($page + $ecart) ))
      $pages .= '<span onclick="gotopage(' . ($p ) . ');" class="naviButton">' . ($p) . '</span>';
    elseif (($page < ($ecart + 2)) && ($p < ($max - $ecart + 2) ))          // si je suis dans les premieres pages ...
      $pages .= '<span onclick="gotopage(' . ($p ) . ');" class="naviButton">' . ($p) . '</span>';
    elseif (($page >= ($npages - $ecart - 2)) && ($p >= ($npages - (2 * $ecart) - 2) ))  // si je suis dans les dernieres pages ...
      $pages .= '<span onclick="gotopage(' . ($p ) . ');" class="naviButton">' . ($p) . '</span>';
    elseif ($p == ($npages - 1)) // c"est la derniere
      $pages .= '<span onclick="gotopage(' . ($p ) . ');" class="naviButton">...' . ($p) . '</span>';
    elseif ($p == 0)    // c"est la premiere
      $pages .= '<span onclick="gotopage(' . ($p ) . ');" class="naviButton">' . ($p) . '...</span>';

    if (($p == $page)
            || ( ( $p >= ($page - $ecart) ) && ( $p <= ($page + $ecart) ))
            || ( ($page < ($ecart + 2)) && ($p < ($max - $ecart + 2) ) )
            || ( ($page >= ($npages - $ecart - 2)) && ($p >= ($npages - (2 * $ecart) - 2) ) )
            || ( $p == 0)
    )
      $pages .= '<span class="naviButton" style="cursor:default;"> - </span>';
  }
}
else
{
  for ($p = 1; $p < $npages; $p++)
  {
    if ($p == $page)
      $pages .= '<span class="naviButton sel">' . ($p) . '</span>';
    else
      $pages .= '<span onclick="gotopage(' . ($p) . ');" class="naviButton">' . ($p) . '</span>';
    if ($p  < $npages)
      $pages .= '<span class="naviButton" style="cursor:default;"> - </span>';
  }
}

$string2 = $pages . '<div class="navigButtons">';
$string2.= '<div id="PREV_PAGE" class="PREV_PAGE"></div>';
$string2.= '<div id="NEXT_PAGE" class="NEXT_PAGE"></div>';
$string2.= '</div>';
?>
<script type="text/javascript">
  $(document).ready(function(){
    $("#navigation").empty().append("<?php echo p4string::JSstring($string2) ?>");

<?php
if ($page != 0 && $nbanswers)
{
  ?>
    $("#PREV_PAGE").bind('click',function(){gotopage(<?php echo ($page - 1) ?>)});
  <?php
}
else
{
  ?>
    $("#PREV_PAGE").unbind('click');
  <?php
}
if ($page != $npages - 1 && $nbanswers)
{
  ?>
    $("#NEXT_PAGE").bind('click',function(){gotopage(<?php echo ($page + 1) ?>)});
  <?php
}
else
{
  ?>
    $("#NEXT_PAGE").unbind('click');
<?php } ?>
});
</script>
<?php

$layoutmode = "grid";
if ($mod_col == 1)
  $layoutmode = "list";
else
  $layoutmode = "grid";

$count = $result->get_datas();

$i = 0;

if (count($result->get_datas()) > 0)
{
  ?><div><table id="grid" cellpadding="0" cellspacing="0" border="0" style="xwidth:95%;"><?php
  if ($mod_col == 1) // MODE LISTE
  {
    ?><tr style="visibility:hidden"><td class="w160px" /><td /></tr><?php
  }
  else // MODE GRILLE
  {
    ?><tr style="visibility:hidden"><?php
    for ($ii = 0; $ii < $mod_col; $ii++)
    {
      ?><td class="w160px"></td><?php
    }
    ?></tr><?php
    }

    $core = \bootstrap::getCore();
    $twig = $core->getTwig();

    foreach ($result->get_datas() as $record)
    {
      /* @var $record record_adapter */
      $base_id = $record->get_base_id();
      $sbas_id = $record->get_sbas_id();

      $thumbnail = $record->get_thumbnail();

      $docType = $record->get_type();

      $title = $record->get_title();
      $light_info = $twig->render('common/technical_datas.twig', array('record' => $record));
      $caption = $twig->render('common/caption.html', array('view' => 'answer', 'record' => $record));


      if ($i == 0)
      {
      ?><tr><?php
    }
    if (($i % $mod_col == 0 && $i != 0))
    {
      ?></tr><tr><?php
      }
      if ($mod_col == 1 && $i != 0)
      {
      ?></tr><tr style="height:20px;">
            <td colspan="2" class="td_mod_lst_img"><hr></td>
          </tr><tr><?php
    }

    if ($mod_col == 1)
    {
      ?><td valign="top" class="td_mod_lst_desc"><?php
    }
    else
    {
      ?><td class="w160px"><?php
    }
    ?><div class="diapo w160px" style="margin-bottom:0;border-bottom:none;">
              <div class="title"><?php echo $title ?></div><?php


        $status = '';
        $status .= '<div class="status">';
        $status .= $record->get_status_icons();
        $status .= '</div>';

        echo $status;

        $isVideo = ($docType == 'video');
        $isAudio = ($docType == 'audio');
        $isImage = ($docType == 'image');
        $isDocument = ($docType == 'document');


        $sd = $record->get_subdefs();

        $isImage = false;
        $isDocument = false;
        if (!$isVideo && !$isAudio)
        {
          $isImage = true;
        }

    ?><table cellpadding="0" cellspacing="0" style="margin: 0pt auto;"><?php
    ?><tr class="h160px"><?php
    ?><td class="image w160px h160px"><?php
          if ($isVideo)
          {
            $duration = $record->get_formated_duration();
            if ($duration != '')
              echo '<div class="dmco_text duration">' . $duration . '</div>';
          }
          if ($isAudio)
          {
            $duration = $record->get_formated_duration();
            if ($duration != '')
              echo '<div class="dmco_text duration">' . $duration . '</div>';
          }

          $onclick = "";

          if ($record->is_grouping())
          {
            $onclick = 'openPreview(\'REG\',0,\'' . $sbas_id . '_' . $record->get_record_id() . '\');';
          }
          else
          {
            $onclick = 'openPreview(\'RESULT\',' . $record->get_number() . ');';
          }

          if ($mod_col == '1')
            $pic_roll = '/prod/tooltip/preview/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/';
          else
            $pic_roll = '/prod/tooltip/caption/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/answer/';

          $pic_roll = str_replace(array('&', '"'), array('&amp;', '&quot;'), $pic_roll);
    ?><img style="<?php if ($thumbnail->get_width() > $thumbnail->get_height())
              { ?>width:128px;<?php }
            else
            { ?>height:128px;<?php } ?>" onclick="<?php echo $onclick ?>" class=" captionTips"  id="IMG<?php echo $record->get_base_id() ?>_<?php echo $record->get_record_id() ?>"  src="<?php echo $thumbnail->get_url() ?>"  tooltipsrc="<?php echo ($pic_roll) ?>" />
                  </td>
                </tr>
              </table>
            </div>
            <div class="diapo w160px" style="border-top:none;"><?php ?><div class="buttons"><?php
              $minilogos = "";

              $minilogos .= '<div class="minilogos">' . collection::getLogo($record->get_base_id());
              $minilogos .= '</div>';
              $sbas_id = $record->get_sbas_id();
              echo $minilogos;

              if (
                      $ACL->has_right_on_base($record->get_base_id(), 'candwnldpreview') ||
                      $ACL->has_right_on_base($record->get_base_id(), 'candwnldhd') ||
                      $ACL->has_right_on_base($record->get_base_id(), 'cancmd')
              )
              {
      ?><div class="downloader" title="<?php echo _('action : exporter') ?>" onclick="evt_dwnl('<?php echo $sbas_id ?>_<?php echo $record->get_record_id() ?>');"></div><?php
        }
    ?>
                <div class="printer" title="<?php echo _('action : print') ?>" onClick="evt_print('<?php echo $sbas_id ?>_<?php echo $record->get_record_id() ?>');"></div>
                <?php
                if ($ACL->has_right_on_base($record->get_base_id(), "canputinalbum"))
                {
                  ?><div class="baskAdder" title="<?php echo _('action : ajouter au panier') ?>" onClick="evt_add_in_chutier('<?php echo $record->get_base_id() ?>', '<?php echo $record->get_record_id() ?>');"></div><?php
          }
          if ($mod_col != '1')
          {
                  ?>
                  <div style="margin-right:3px;" class="infoTips" id="INFO<?php echo $record->get_base_id() ?>_<?php echo $record->get_record_id() ?>" tooltipsrc="/prod/tooltip/tc_datas/<?php echo $record->get_sbas_id() ?>/<?php echo $record->get_record_id() ?>/"></div>
                  <?php
                  try
                  {
                    if ($record->get_preview()->is_physically_present())
                    {
                      ?>
                      <div class="previewTips" tooltipsrc="/prod/tooltip/preview/<?php echo $record->get_sbas_id(); ?>/<?php echo $record->get_record_id(); ?>/" id="ZOOM<?php echo $record->get_base_id() ?>_<?php echo $record->get_record_id() ?>">&nbsp;</div>
                      <?php
                    }
                  }
                  catch (Exception $e)
                  {

                  }
                }
                ?></div><?php
                ?></div><?php
                ?></td><?php
            if ($mod_col == 1) // 1X10 ou 1X100
            {
                  ?><td valign="top"><?php
                  ?><div class="desc1"><?php
                  ?><div class="caption" class="desc2"><?php echo ($caption . '<hr/>' . $light_info) ?></div><?php
                  ?></div><?php
                  ?></td><?php
        }

        $i++;
      }
              ?></tr>
    </table>
    <script type="text/javascript">
      $(document).ready(function(){

        p4.tot = <?php echo $result->get_count_available_results(); ?>;
        p4.tot_options = '<?php echo serialize($options) ?>';
        p4.tot_query = '<?php echo $parm['qry'] ?>';

      });

    </script>
  </div><?php
    }
    else
    {
              ?><div><?php echo _('reponses:: Votre recherche ne retourne aucun resultat'); ?></div><?php
  phrasea::getHome('HELP', 'client');
}

function proposalsToHTML(&$proposals)
{

  $html = '<div class="proposals">';
  $b = true;
  foreach ($proposals["BASES"] as $zbase)
  {
    if ((int) (count($proposals["BASES"]) > 1) && count($zbase["TERMS"]) > 0)
    {
      $style = $b ? 'style="margin-top:0px;"' : '';
      $b = false;
      $html .= "<h1 $style>" . sprintf(_('reponses::propositions pour la base %s'), htmlentities($zbase["NAME"])) . "</h1>";
    }
    $t = true;
    foreach ($zbase["TERMS"] as $path => $props)
    {
      $style = $t ? 'style="margin-top:0px;"' : '';
      $t = false;
      $html .= "<h2 $style>" . sprintf(_('reponses::propositions pour le terme %s'), htmlentities($props["TERM"])) . "</h2>";
      $html .= $props["HTML"];
    }
  }
  $html .= '</div>';

  return($html);
}

