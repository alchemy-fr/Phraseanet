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
 * @package
 * @copyright   (c) 2004 Alchemy
 * @version     3.5
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 * @see         http://developer.phraseanet.com
 *
 */
require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();

$request = http_request::getInstance();

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

if (!isset($parm))
  $parm = $request->get_parms("bas", "qry", "pag"
                  , "sel", "ord", "sort", "stemme"
                  , "search_type", "recordtype", "status", "fields", "datemin", "datemax", "datefield");


$parm['sel'] = explode(';', $parm['sel']);

if (!$parm['bas'])
  $parm['bas'] = array();


$parm["pag"] = (int) $parm["pag"];
$mod = $user->getPrefs('view');
//var_dump($session->get_ses_id(), phrasea_open_session($session->get_ses_id(), $session->get_usr_id()));
function dmicrotime_float($message = '', $stack = 'def')
{
  static $last = array();
  list($usec, $sec) = explode(' ', microtime());

  $new = ((float) $usec + (float) $sec);

//  if (isset($last[$stack]) && $message)
//    echo "$stack \t\t temps : $message " . ($new - $last[$stack]) . "\n";

  $last[$stack] = $new;

  return ($new - $last[$stack]);
}

dmicrotime_float();
$json = array();

//$options = array(
//    'type' => $parm['search_type'],
//    'bases' => $parm['bas'],
//    'champs' => $parm['fields'],
//    'status' => $parm['status'],
//    'recordtype' => (in_array($parm['recordtype'], array('image', 'video', 'audio', 'document', 'flash')) ? $parm['recordtype'] : ''),
//    'date' => array(
//        'minbound' => $parm['datemin'],
//        'maxbound' => $parm['datemax'],
//        'field' => explode('|', $parm['datefield'])
//    )
//);

$options = new searchEngine_options();


$options->set_bases($parm['bas'], $user->ACL());
if (!is_array($parm['fields']))
  $parm['fields'] = array();
$options->set_fields($parm['fields']);
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

$perPage = (int) $user->getPrefs('images_per_page');

$search_engine = new searchEngine_adapter($registry);
$search_engine->set_options($options);


if ($parm['pag'] < 1)
{
  $search_engine->set_is_first_page(true);
  $search_engine->reset_cache();
  $parm['pag'] = 1;
}

$result = $search_engine->query_per_page($parm['qry'], $parm["pag"], $perPage);



$proposals = $search_engine->is_first_page() ? $result->get_propositions() : false;

$npages = $result->get_total_pages();


$page = $result->get_current_page();

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


$explain = "<div id=\"explainResults\" class=\"myexplain\">";

$explain .= "<img src=\"/skins/icons/answers.gif\" /><span><b>";

if ($result->get_count_total_results() != $result->get_count_available_results())
{
  $explain .= sprintf(_('reponses:: %d Resultats rappatries sur un total de %d trouves'), $result->get_count_available_results(), $result->get_count_total_results());
}
else
{
  $explain .= sprintf(_('reponses:: %d Resultats'), $result->get_count_total_results());
}

$explain .= " </b></span>";
$explain .= '<br><div>' . $result->get_query_time() . ' s</div>dans index ' . $result->get_search_indexes();
$explain .= "</div>";



$infoResult = ' | <a href="#" class="infoDialog" infos="' . str_replace('"', '&quot;', $explain) . '">' . sprintf(_('reponses:: %d reponses'), $result->get_count_total_results()) . '</a> | ' . sprintf(_('reponses:: %s documents selectionnes'), '<span id="nbrecsel"></span>');

$json['infos'] = $infoResult;
$json['navigation'] = $string;

$prop = null;
$propal_n = 0;

if ($search_engine->is_first_page())
{
  $propals = $result->get_suggestions();
  if (count($propals) > 0)
  {
    foreach ($propals as $prop_array)
    {
      if ($prop_array['value'] !== $parm['qry'] && $prop_array['hits'] > $result->get_count_total_results())
      {
        $prop = $prop_array['value'];
        break;
      }
    }
  }
}

$twig = new supertwig();
$twig->addFilter(array('sbasFromBas' => 'phrasea::sbasFromBas'));

if($result->get_count_total_results() === 0)
{
  $template = 'prod/results/help.twig';
}
else
{
  if ($mod == 'thumbs')
  {
    $template = 'prod/results/answergrid.html';
  }
  else
  {
    $template = 'prod/results/answerlist.html';
  }
}


$json['results'] = $twig->render($template, array(
            'results' => $result,
            'GV_social_tools' => $registry->get('GV_social_tools'),
            'array_selected' => $parm['sel'],
            'highlight' => $search_engine->get_query(),
            'searchEngine' => $search_engine,
            'suggestions' => $prop
                )
);


$json['query'] = $parm['qry'];
$json['phrasea_props'] = $proposals;
$json['total_answers'] = (int) $result->get_count_available_results();
$json['next_page'] = ($page < $npages && $result->get_count_available_results() > 0) ? ($page + 1) : false;
$json['prev_page'] = ($page > 1 && $result->get_count_available_results() > 0) ? ($page - 1) : false;
$json['form'] = $form;
echo p4string::jsonencode($json);

