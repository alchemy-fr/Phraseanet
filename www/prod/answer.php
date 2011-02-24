<?php
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
$session = session::getInstance();

require_once( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

$request = httpRequest::getInstance();

if (!isset($parm))
  $parm = $request->get_parms("bas", "qry", "pag"
                  , "sel", "ord"
                  , "search_type", "recordtype", "status", "fields", "datemin", "datemax", "datefield");


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
  $request = httpRequest::getInstance();
  if ($request->is_ajax())
  {
    echo _('Votre session est expiree, veuillez vous reconnecter');
  }
  else
  {
    header("Location: /login/");
  }
  exit();
}

if (!($ph_session = phrasea_open_session($ses_id, $usr_id)))
{
  die();
}

if ($parm["ord"] === NULL)
  $parm["ord"] = PHRASEA_ORDER_DESC;
else
  $parm["ord"] = (int) $parm["ord"];

$parm['sel'] = explode(';', $parm['sel']);

if (!$parm['bas'])
  $parm['bas'] = array();

if (!$parm["pag"] === NULL)
  $parm["pag"] = "0";

if (trim($parm["qry"]) === '')
  $parm["qry"] = "all";

$mod = user::getPrefs('view');

$options = array(
    'type' => $parm['search_type'],
    'bases' => $parm['bas'],
    'champs' => $parm['fields'],
    'status' => $parm['status'],
    'date' => array(
        'minbound' => $parm['datemin'],
        'maxbound' => $parm['datemax'],
        'field' => explode('|', $parm['datefield'])
    )
);

if ($parm['recordtype'] != '' && in_array($parm['recordtype'], array('image', 'video', 'audio', 'document', 'flash')))
{
  $parm['qry'] .= ' AND recordtype=' . $parm['recordtype'];
}


$query = new query($options);

$result = $query->results($parm['qry'], $parm["pag"]); //$parm['search_type'],

$proposals = trim($parm['pag']) === '' ? $query->proposals() : false;

$npages = $result['pages'];
$page = $result['current_page'];
$string = '';

if ($npages > 1)
{

  $d2top = ($npages - $page);
  $d2bottom = $page;

  if (min($d2top, $d2bottom) < 4)
  {
    if ($d2bottom < 4)
    {
      for ($i = 1; ($i <= 4 && (($i <= $npages) === true)); $i++)
      {
        if ($i == $page)
          $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" />';
        else
          $string .= "<a onclick='gotopage(" . $i . ");return false;'>" . $i . "</a>";
      }
      if ($npages > 4)
        $string .= "<a onclick='gotopage(" . ($npages) . ");return false;'>&gt;&gt;</a>";
    }
    else
    {
      $start = $npages - 4;
      if (($start) > 0)
        $string .= "<a onclick='gotopage(1);return false;'>&lt;&lt;</a>";
      else
        $start = 1;
      for ($i = ($start); $i <= $npages; $i++)
      {
        if ($i == $page)
          $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" />';
        else
          $string .= "<a onclick='gotopage(" . $i . ");return false;'>" . $i . "</a>";
      }
    }
  }
  else
  {
    $string .= "<a onclick='gotopage(1);return false;'>&lt;&lt;</a>";

    for ($i = ($page - 2); $i <= ($page + 2); $i++)
    {
      if ($i == $page)
        $string .= '<input onkeypress="if(event.keyCode == 13 && !isNaN(parseInt(this.value)))gotopage(parseInt(this.value))" type="text" value="' . $i . '" size="' . (strlen((string) $i)) . '" />';
      else
        $string .= "<a onclick='gotopage(" . $i . ");return false;'>" . $i . "</a>";
    }

    $string .= "<a onclick='gotopage(" . ($npages) . ");return false;'>&gt;&gt;</a>";
  }
}
$string .= '<div style="display:none;"><div id="NEXT_PAGE"></div><div id="PREV_PAGE"></div></div>';

$explain = $result['explain'];

$infoResult = ' | <a href="#" class="infoDialog" infos="' . str_replace('"', '&quot;', $explain) . '">' . sprintf(_('reponses:: %d reponses'), $session->prod['query']['nba']) . '</a> | ' . sprintf(_('reponses:: %s documents selectionnes'), '<span id="nbrecsel"></span>');

echo "<script type='text/javascript'>$('#tool_results').empty().append('" . str_replace("'", "\'", $infoResult) . "');</script>";
echo "<script type='text/javascript'>$('#tool_navigate').empty().append('" . str_replace("'", "\'", $string) . "');</script>";

$rsScreen = $result['result'];

if (count($rsScreen) > 0)
{
  if ($mod == 'thumbs')
    require("answergrid.php");
  else
    require("answerlist.php");
}
else
{
  echo '<div style="float:left;">';
  phrasea::getHome('HELP', 'prod');
  echo '</div>';
}

function proposalsToHTML(&$proposals)
{
  $html = "<div class=\"proposals\">";

  $nbasesWprop = count($proposals["BASES"]);

  $b = 0;
  foreach ($proposals["BASES"] as $zbase)
  {
    if ((int) (count($zbase["TERMS"]) > 0))
    {
      if (($nbasesWprop > 1))
      {
        $style = $b == 0 ? "style=\"margin-top:0px;\"" : "";
        $html .= "<h1" . $style . ">" . sprintf(_('reponses::propositions pour la base %s'), $zbase["NAME"]) . "</h1>";
      }
      $t = 0;
      foreach ($zbase["TERMS"] as $path => $props)
      {
        $style = $t == 0 ? "style=\"margin-top:0px;\"" : "";
        $html .= "<h2 $style>" . sprintf(_('reponses::propositions pour le terme %s'), $props["TERM"]) . "</h2>";
        $html .= $props["HTML"];
        $t++;
      }
      $b++;
    }
  }
  $html .= "</div>";
  return($html);
}
?>
<script type="text/javascript">
  $(document).ready(function(){
<?php
if ($proposals)
{
?>
          $('#proposals').empty().append("<?php echo $proposals ?>");
          $('.activeproposals').show();
<?php
}
elseif (trim($parm['pag']) === '')
{
?>
          $('#proposals').empty();
<?php
}
?>
<?php if ($page > 1 && $session->prod['query']['nba'] > 0)
{ ?> 
        $("#PREV_PAGE").bind('click',function(){gotopage(<?php echo ($page - 1) ?>)});
<?php }
else
{ ?>
        $("#PREV_PAGE").unbind('click');
<?php }
if ($page < $npages && $session->prod['query']['nba'] > 0)
{ ?>
         $("#NEXT_PAGE").bind('click',function(){gotopage(<?php echo ($page + 1) ?>)});
<?php }
else
{ ?>
        $("#NEXT_PAGE").unbind('click');
<?php } ?>
      p4.tot = <?php echo ((is_int((int) $session->prod['query']['nba']) && (int) $session->prod['query']['nba'] >= 0) ? (int) $session->prod['query']['nba'] : 0) ?>;
    });
</script>

