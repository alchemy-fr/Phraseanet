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
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";

/* get all the post parameters from report.php's form */

$request = http_request::getInstance();
$parm = $request->get_parms("dmin", // date minimal of the reporting
                            "dmax", // date maximal of the reporting
                            "page", // current page of the reporting
                            "limit", // numbers of result by page displayed in the reporting
                            "tbl", // action requested
                            "collection", // list of collection which concerns the reporting
                            "user", // id of user which concern the reporting
                            "precise", // 1 = precision on content of the doc we're lookin for; 2 = precison on id of the document we're looking for
                            "order", // order result of the reporting
                            "champ", // precise the field we want order
                            "word", // precise the word we're lookin for in our documents
                            "sbasid", // the report relates to this base iD
                            "rid", // precise the id of the document we are looking for
                            "filter_column", // name of the colonne we want applied the filter
                            "filter_value", // value of the filter
                            "liste", // default = off, if on, apply the new filter
                            "liste_filter", // memorize the current(s) applied filters
                            "conf", // default = off, if on, apply the new configuration
                            "list_column", // contain the list of the column the user wants to see
                            "groupby", // name of the column that the user wants group
                            "societe", // the name of the selectionned firm
                            "fonction", // the name of the selectionned function
                            "activite", // the name of the selectionned activity
                            "pays", // the name of the selectionned country
                            "on", // this field contain the name of the column the user wants display the download by
                            "top", // this field contains the number of the top questions he wants to see
                            "from"
);

$twig = new supertwig();

$twig->addFilter(array(
    'sbas_names' => 'phrasea::sbas_names',
    'str_replace' => 'str_replace',
    'serialize' => 'serialize',
    'strval' => 'strval'
));

$conf_info_usr = array(
    'config' => array(
        'photo' => array(_('report:: document'), 0, 0, 0, 0),
        'record_id' => array(_('report:: record id'), 0, 0, 0, 0),
        'date' => array(_('report:: date'), 0, 0, 0, 0),
        'type' => array(_('phrseanet:: sous definition'), 0, 0, 0, 0),
        'titre' => array(_('report:: titre'), 0, 0, 0, 0),
        'taille' => array(_('report:: poids'), 0, 0, 0, 0)
    ),
    'conf' => array(
        'identifiant' => array(_('report:: identifiant'), 0, 0, 0, 0),
        'nom' => array(_('report:: nom'), 0, 0, 0, 0),
        'mail' => array(_('report:: email'), 0, 0, 0, 0),
        'adresse' => array(_('report:: adresse'), 0, 0, 0, 0),
        'tel' => array(_('report:: telephone'), 0, 0, 0, 0)
    ),
    'config_cnx' => array(
        'ddate' => array(_('report:: date'), 0, 0, 0, 0),
        'ip' => array(_('report:: IP'), 0, 0, 0, 0),
        'appli' => array(_('report:: modules'), 0, 0, 0, 0),
    ),
    'config_dl' => array(
        'ddate' => array(_('report:: date'), 0, 0, 0, 0),
        'record_id' => array(_('report:: record id'), 0, 1, 0, 0),
        'final' => array(_('phrseanet:: sous definition'), 0, 0, 0, 0),
        'coll_id' => array(_('report:: collections'), 0, 0, 0, 0),
        'comment' => array(_('report:: commentaire'), 0, 0, 0, 0),
    ),
    'config_ask' => array(
        'search' => array(_('report:: question'), 0, 0, 0, 0),
        'ddate' => array(_('report:: date'), 0, 0, 0, 0)
    )
);
#############################################################################################################################

function getFilterField($param)
{
  $filter_column = explode(' ', $param['filter_column']);
  $column = $filter_column[0];

  return $column;
}

function doOrder($obj, $param)
{
  (!empty($param['order']) && !empty($param['champ'])) ? $obj->setOrder($param['champ'], $param['order']) : "";
}

function doLimit($obj, $param)
{
  (!empty($param['page']) && !empty($param['limit'])) ? $obj->setLimit($param['page'], $param['limit']) : "";
}

function doFilter($obj, $param, $twig)
{
  $cor = $obj->getTransQueryString();
  $currentfilter = unserializeFilter($param['liste_filter']);
  $filter = new module_report_filter($currentfilter, $cor);

  if (!empty($param['filter_column']))
  {
    $field = getFilterField($param);
    $value = $param['filter_value'];

    if ($param['liste'] == "on")
    {
      $tab = $obj->colFilter($field);
      displayColValue($tab, $field, $twig);
    }

    if ($field == $value)
      $filter->removeFilter($field);
    else
      $filter->addFilter($field, '=', $value);
  }

  return $filter;
}

function passFilter($filter, $obj)
{
  $tab_filter = $filter->getTabFilter();
  $obj->setFilter($tab_filter);
}

function doPreff($conf, $param)
{
  $pref = module_report::getPreff($param['sbasid']);
  foreach ($pref as $key => $field)
    $conf_pref[$field] = array($field, 0, 0, 0, 0);
  $conf = array_merge($conf, $conf_pref);

  return $conf;
}

function doReport($obj, $param, $conf, $twig, $what = false)
{
  $conf = doUserConf($conf, $param);
  displayListColumn($conf, $param, $twig);
  doOrder($obj, $param);

  $filter = doFilter($obj, $param, $twig);
  passFilter($filter, $obj);
  $posting_filter = $filter->getPostingFilter();
  $active_column = $filter->getActiveColumn();

  if ($param['precise'] == 1)
    $dl->addfilter('xml', 'LIKE', $param['word']);
  elseif ($param['precise'] == 2)
    $dl->addfilter('record_id', '=', $param['word']);

  groupBy($obj, $param, $twig);
  doLimit($obj, $param);

  if (!$what)
    $tab = $obj->buildTab($conf);
  else
    $tab = $obj->buildTab($conf, $what, $param['tbl']);

  return (array('rs' => $tab, 'filter' => $posting_filter, 'column' => $active_column));
}

function doHtml($report, $param, $twig, $template, $type = false)
{
  $var = array(
      'result' => (isset($report['rs'])) ? $report['rs'] : $report,
      'report' => $report,
      'currentfilter' => isset($report['filter']) ? $report['filter'] : "",
      'param' => $param,
      'is_infouser' => false,
      'is_nav' => false,
      'is_groupby' => false,
      'is_plot' => false,
      'meta' => true
  );

  if ($type)
  {
    switch ($type)
    {
      case "user" :
        $var['is_infouser'] = true;
        break;
      case "nav" :
        $var['is_nav'] = true;
        break;
      case "group" :
        $var['is_groupby'] = true;
        break;
      case "plot" :
        $var['is_plot'] = true;
        break;
    }
  }

  return ($twig->render($template, $var));
}

function sendReport($html, $report = false, $title = false, $display_nav = false)
{
  if ($report)
  {
    $t = array(
        'rs' => $html,
        'next' => intval($report['rs']['next_page']), //Number of the next page
        'prev' => intval($report['rs']['previous_page']), //Number of the previoous page
        'page' => intval($report['rs']['page']), //The current page
        'limit' => $report['rs']['nb_record']
    );
  }
  else
  {
    $t = array(
        'rs' => $html,
        'display_nav' => $display_nav,
        'title' => $title
    );
  }
  echo json_encode($t);
}

function getBasId($param)
{
  try
  {
    $record = new record_adapter($param['sbasid'], $param['rid']);

    return $record->get_base_id();
  }
  catch (Exception $e)
  {

  }

  return false;
}

function unserializeFilter($serialized_filter)
{
  $tab_filter = array();
  if (!empty($serialized_filter))
  {
    $tab_filter = @unserialize(urldecode($serialized_filter));
  }

  return $tab_filter;
}

function doUserConf($conf, $param)
{
  $registry = registry::get_instance();
  if ($registry->get('GV_anonymousReport') == true)
  {
    if (isset($conf['user']))
      unset($conf['user']);
    if (isset($conf['ip']))
      unset($conf['ip']);
  }

  if (!empty($param['list_column']))
  {
    $new_conf = array();
    $new_conf = $conf;
    $x = explode(",", $param['list_column']);

    foreach ($conf as $key => $value)
    {
      if (!in_array($key, $x))
        unset($new_conf[$key]);
    }

    return $new_conf;
  }
  else

    return $conf;
}

function displayListColumn($conf, $param, $twig)
{
  if ($param['conf'] == "on")
  {
    $html = $twig->render('report/listColumn.twig', array(
                'conf' => $conf,
                'param' => $param,
            ));
    $t = array('liste' => $html, "title" => _("configuration"));
    echo json_encode($t);
    exit();
  }
}

function groupBy($obj, $param, $twig, $on = false)
{
  //Contains  the name of the column where the group by is applied
  (!empty($param['groupby']) ? $groupby = explode(' ', $param['groupby']) : $groupby = false);
  //If users ask for group by, display the good array, result is encoded in Json , exit the function.
  if ($groupby)
  {
    $report = $obj->buildTab(false, $groupby[0], $on);
    $html = doHtml($report, $param, $twig, 'report/report.twig', 'group');
    $title = "Groupement des resultats sur le champ " . $report['display'][$report['allChamps'][0]]['title'];
    sendReport($html, false, $title);
    exit();
  }
}

function displayColValue($tab, $column, $twig, $on = false)
{
  $test = $twig->render('report/colFilter.twig', array(
              'result' => $tab,
              'field' => $column
          ));
  $t = array('diag' => $test, "title" => sprintf(_("filtrer les resultats sur la colonne %s"), $column));
  echo json_encode($t);
  exit();
}

function getHistory($obj, $param, $twig, $conf, $dl = false, $title)
{
  $filter = doFilter($obj, $param, $twig);

  if (!empty($param['user']) && empty($param['on']))
    $filter->addfilter('usrid', '=', $param['user']);
  elseif (!empty($param['on']) && !empty($param['user']))
    $filter->addfilter($param['on'], '=', $param['user']);
  if ($dl)
    $filter->addfilter("(log_docs.final = 'document'", "OR", "log_docs.final = 'preview')");

  passFilter($filter, $obj);
  $obj->setOrder('ddate', 'DESC');

  $report = $obj->buildTab($conf);

  $report['title'] = $title;
  $report['config'] = 0;

  $html = doHtml($report, $param, $twig, 'report.twig');
  $request = $obj->req;

  return(array('html' => $html, 'req' => $request));
}

################################################ACTION FUNCTIONS#######################################################

function cnx($param, $twig)
{
  $cnx = new module_report_connexion($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  $conf = array(
      'user' => array(_('phraseanet::utilisateurs'), 1, 1, 1, 1),
      'ddate' => array(_('report:: date'), 1, 0, 1, 1),
      'ip' => array(_('report:: IP'), 1, 0, 0, 0),
      'appli' => array(_('report:: modules'), 1, 0, 0, 0)
  );
  $report = doReport($cnx, $param, $conf, $twig);
  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html, $report);
}

/* generate all the html string to display all the valid download in <table></table>, the result is encoded in json */

function gen($param, $twig)
{
  $dl = new module_report_download($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  $conf = array(
      'user' => array(_('report:: utilisateurs'), 1, 1, 1, 1),
      'ddate' => array(_('report:: date'), 1, 0, 1, 1),
      'record_id' => array(_('report:: record id'), 1, 1, 1, 1),
      'final' => array(_('phrseanet:: sous definition'), 1, 0, 1, 1),
      'coll_id' => array(_('report:: collections'), 1, 0, 1, 1)
  );
  //$conf = doPreff($conf, $param);
  $report = doReport($dl, $param, $conf, $twig);
  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html, $report);
}

/* generate all the html string to display all the valid question in <table></table>, the result is encoded in json */

function ask($param, $twig)
{
  $ask = new module_report_question($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  $conf = array(
      'user' => array(_('report:: utilisateur'), 1, 1, 1, 1),
      'search' => array(_('report:: question'), 1, 0, 1, 1),
      'ddate' => array(_('report:: date'), 1, 0, 1, 1)
  );
  $report = doReport($ask, $param, $conf, $twig);
  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html, $report);
}

/* generate the html code to display the download by doc (records or string in xml description), the result is encoded in json */

function doc($param, $twig)
{
  $conf = array(
      'telechargement' => array(_('report:: telechargements'), 1, 0, 0, 0),
      'record_id' => array(_('report:: record id'), 1, 1, 1, 0),
      'final' => array(_('phrseanet:: sous definition'), 1, 0, 1, 1),
      'file' => array(_('report:: fichier'), 1, 0, 0, 1),
      'mime' => array(_('report:: type'), 1, 0, 1, 1),
      'size' => array(_('report:: taille'), 1, 0, 1, 1)
  );
  $dl = new module_report_download($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  $conf = doPreff($conf, $param);
  $report = doReport($dl, $param, $conf, $twig, 'record_id');
  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html, $report);
}

/* generate the html string to display the result from different report (see below) in <table></table>, the result is encoded in json */

function cnxb($param, $twig)
{
  $nav = new module_report_nav($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  $conf_nav = array('nav' => array(_('report:: navigateur'), 0, 1, 0, 0),
      'nb' => array(_('report:: nombre'), 0, 0, 0, 0),
      'pourcent' => array(_('report:: pourcentage'), 0, 0, 0, 0)
  );
  $conf_combo = array('combo' => array(_('report:: navigateurs et plateforme'), 0, 0, 0, 0),
      'nb' => array(_('report:: nombre'), 0, 0, 0, 0),
      'pourcent' => array(_('report:: pourcentage'), 0, 0, 0, 0)
  );
  $conf_os = array('os' => array(_('report:: plateforme'), 0, 0, 0, 0),
      'nb' => array(_('report:: nombre'), 0, 0, 0, 0),
      'pourcent' => array(_('report:: pourcentage'), 0, 0, 0, 0)
  );
  $conf_res = array('res' => array(_('report:: resolution'), 0, 0, 0, 0),
      'nb' => array(_('report:: nombre'), 0, 0, 0, 0),
      'pourcent' => array(_('report:: pourcentage'), 0, 0, 0, 0)
  );
  $conf_mod = array('appli' => array(_('report:: module'), 0, 0, 0, 0),
      'nb' => array(_('report:: nombre'), 0, 0, 0, 0),
      'pourcent' => array(_('report:: pourcentage'), 0, 0, 0, 0)
  );
  $report = array(
      'nav' => $nav->buildTabNav($conf_nav),
      'os' => $nav->buildTabOs($conf_os),
      'res' => $nav->buildTabRes($conf_res),
      'mod' => $nav->buildTabModule($conf_mod),
      'combo' => $nav->buildTabCombo($conf_combo)
  );
  $html = doHtml($report, $param, $twig, 'report/report.twig', 'nav');
  sendReport($html);
}

/* generate the html string to display the number of connexion by user in <table></table>, the result is encoded in json */

function cnxu($param, $twig)
{
  $conf = array(
      $param['on'] => array("", 0, 0, 0, 0),
      'connexion' => array(_('report::Connexions'), 0, 0, 0, 0)
  );
  $connex = new module_report_activity($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  doLimit($connex, $param);
  $report = $connex->getConnexionBase(false, $param['on']);

  isset($report['display']['user']) ? $report['display']['user']['title'] = _('phraseanet::utilisateurs') : "";
  isset($report['display']['user']) ? $report['display']['user']['bound'] = 1 : "";

  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html);
}

/* generate all the html string to display the top 20 question by databox in <table></table>, the result is encoded in json */

function bestOf($param, $twig)
{
  $conf = array(
      'search' => array(_('report:: question'), 0, 0, 0, 0),
      'nb' => array(_('report:: nombre'), 0, 0, 0, 0),
      'nb_rep' => array(_('report:: nombre de reponses'), 0, 0, 0, 0)
  );
  $activity = new module_report_activity($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

  $activity->setLimit(1, $param['limit']);
  $activity->nb_top = $param['limit'];

  $report = $activity->getTopQuestion($conf);
  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html);
}

/* generate all the html string to display all the resot of questions <table></table>, the result is encoded in json */

function noBestOf($param, $twig)
{
  $conf = array('search' => array(_('report:: question'), 0, 0, 0, 0),
      'nb' => array(_('report:: nombre'), 0, 0, 0, 0),
      'nb_rep' => array(_('report:: nombre de reponses'), 0, 0, 0, 0)
  );
  $activity = new module_report_activity($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  doLimit($activity, $param);
  $report = $activity->getTopQuestion($conf, true);
  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html);
}

/* generate all the html string to display the users connexions activity by hour in <table></table>, the result is encoded in json */

function tableSiteActivityPerHours($param, $twig)
{
  $activity = new module_report_activity($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  $report = $activity->getActivityPerHours();
  $html = doHtml($report, $param, $twig, 'report/report.twig', 'plot');
  sendReport($html);
}

/* generate all the html string to display all number of download day by day in <table></table>, the result is encoded in json */

function day($param, $twig)
{
  $conf = array('ddate' => array(_('report:: jour'), 0, 0, 0, 0),
      'total' => array(_('report:: total des telechargements'), 0, 0, 0, 0),
      'preview' => array(_('report:: preview'), 0, 0, 0, 0),
      'document' => array(_('report:: document original'), 0, 0, 0, 0)
  );
  $activity = new module_report_activity($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  $activiy->list_coll_id = $param['collection'];
  doLimit($activity, $param);
  $report = $activity->getDownloadByBaseByDay($conf);
  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html);
}

/* generate all the html string to display all the details of download user by user in <table></table>, the result is encoded in json */

function usr($param, $twig)
{
  $conf = array('user' => array(_('report:: utilisateur'), 0, 1, 0, 0),
      'nbdoc' => array(_('report:: nombre de documents'), 0, 0, 0, 0),
      'poiddoc' => array(_('report:: poids des documents'), 0, 0, 0, 0),
      'nbprev' => array(_('report:: nombre de preview'), 0, 0, 0, 0),
      'poidprev' => array(_('report:: poids des previews'), 0, 0, 0, 0)
  );

  $activity = new module_report_activity($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  doLimit($activity, $param);

  empty($param['on']) ? $on = "user" : $on = $param['on']; //by default always report on user

  $report = $activity->getDetailDownload($conf, $on);
  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html);
}

/* Display basic informations about an user */

function infoUsr($param, $twig, $conf)
{
  $registry = registry::get_instance();
  if ($registry->get('GV_anonymousReport') == true)
  {
    $conf = array(
        $param['on'] => array($param['on'], 0, 0, 0, 0),
        'nb' => array(_('report:: nombre'), 0, 0, 0, 0)
    );
  }

  empty($param['on']) ? $param['on'] = false : "";

  $request = "";
  $params = array();
  $html = "";
  $html_info = "";
  $is_dl = false;

  if ($param['from'] == 'CNXU' || $param['from'] == 'CNX')
  {
    $histo = new module_report_connexion($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $conf_array = $conf['config_cnx'];
    $title = _("report:: historique des connexions");
  }
  elseif ($param['from'] == "USR" || $param['from'] == "GEN")
  {
    $histo = new module_report_download($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $conf_array = $conf['config_dl'];
    $is_dl = true;
    $title = _("report:: historique des telechargements");
  }
  elseif ($param['from'] == "ASK")
  {
    $histo = new module_report_question($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $conf_array = $conf['config_ask'];
    $title = _("report:: historique des questions");
  }

  if (isset($histo))
  {
    $rs = getHistory($histo, $param, $twig, $conf_array, $is_dl, $title);
    $html = $rs['html'];
    $request = $rs['req'];
    $params = $rs['params'];
  }

  $info = new module_report_nav($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  $report = $info->buildTabGrpInfo($request, $params, $param['user'], $conf['conf'], $param['on']);
  $report['periode'] = ""; //delete the periode
  if ($registry->get('GV_anonymousReport') == false)
  {
    $html_info .= doHtml($report, $param, $twig, 'report/report.twig');
    (empty($param['on']) && isset($report['result'])) ? $title = $report['result'][0]['identifiant'] : $title = $param['user'];
  }
  else
    $title = $param['user'];

  sendReport($html_info . $html, false, $title);
}

/* Display some basics informations about a Document */

function what($param, $twig)
{
  $registry = registry::get_instance();

  $config = array(
      'photo' => array(_('report:: document'), 0, 0, 0, 0),
      'record_id' => array(_('report:: record id'), 0, 0, 0, 0),
      'date' => array(_('report:: date'), 0, 0, 0, 0),
      'type' => array(_('phrseanet:: sous definition'), 0, 0, 0, 0),
      'titre' => array(_('report:: titre'), 0, 0, 0, 0),
      'taille' => array(_('report:: poids'), 0, 0, 0, 0)
  );
  $config_dl = array(
      'ddate' => array(_('report:: date'), 0, 0, 0, 0),
      'user' => array(_('report:: utilisateurs'), 0, 0, 0, 0),
      'final' => array(_('phrseanet:: sous definition'), 0, 0, 0, 0),
      'coll_id' => array(_('report:: collections'), 0, 0, 0, 0),
      'comment' => array(_('report:: commentaire'), 0, 0, 0, 0)
  );

  $config_dl = doUserConf($config_dl, $param);

  $html = "";
  $basid = getBasId($param);

  $what = new module_report_nav($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

  $report = $what->buildTabUserWhat($basid, $param['rid'], $config);
  $report['periode'] = "";

  if ($param['from'] == 'TOOL')
    $report['title'] = "";

  $title = $report['result'][0]["titre"];

  $html = doHtml($report, $param, $twig, 'report/info.twig');

  if ($param['from'] == 'TOOL')
    sendReport($html);

  if ($param['from'] != 'DASH')
  {
    $histo = new module_report_download($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

    $filter = doFilter($histo, $param, $twig);
    if (!empty($param['rid']))
      $filter->addfilter('record_id', '=', $param['rid']);

    passFilter($filter, $histo);
    $histo->setOrder('ddate', 'DESC');

    $report = $histo->buildTab($config_dl);
    $report['title'] = _("report:: historique des telechargements");
    $report['config'] = 0;
    $html .= doHtml($report, $param, $twig, 'report/report.twig');
  }

  if ($registry->get('GV_anonymousReport') == false && $param['from'] != 'DOC' && $param['from'] != 'DASH' && $param['from'] != "GEN")
  {
    $conf = array(
        'identifiant' => array(_('report:: identifiant'), 0, 0, 0, 0),
        'nom' => array(_('report:: nom'), 0, 0, 0, 0),
        'mail' => array(_('report:: email'), 0, 0, 0, 0),
        'adresse' => array(_('report:: adresse'), 0, 0, 0, 0),
        'tel' => array(_('report:: telephone'), 0, 0, 0, 0)
    );
    $info = new module_report_nav($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $report = $info->buildTabGrpInfo(false, array(), $param['user'], $conf, false);

    $report['periode'] = "";
    $report['config'] = 0;
    $report['title'] = _('report:: utilisateur');

    $html .= doHtml($report, $param, $twig, 'report/report.twig');
  }
  sendReport($html, false, $title);
}

/* Display image when click in the dashboard */

function infoNav($param, $twig)
{
  $conf = array(
      'version' => array(_('report::version '), 0, 0, 0, 0),
      'nb' => array(_('report:: nombre'), 0, 0, 0, 0)
  );
  $infonav = new module_report_nav($param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
  $report = $infonav->buildTabInfoNav($conf, $param['user']);
  $html = doHtml($report, $param, $twig, 'report/report.twig');
  sendReport($html, false, $param['user']);
}

################################################SWITCH ACTIONS##############################################################

switch ($param['tbl'])
{
  case "CNX":
    cnx($param, $twig);
    break;

  case "CNXU":
    cnxu($param, $twig);
    break;

  case "CNXB":
    cnxb($param, $twig);
    break;

  case "GEN":
    gen($param, $twig);
    break;

  case "DAY":
    day($param, $twig);
    break;

  case "DOC":
    doc($param, $twig);
    break;

  case "BESTOF":
    bestOf($param, $twig);
    break;

  case "NOBESTOF":
    noBestOf($param, $twig);
    break;

  case "SITEACTIVITY":
    tableSiteActivityPerHours($param, $twig);
    break;

  case "USR":
    usr($param, $twig);
    break;

  case "ASK":
    ask($param, $twig);
    break;

  case "infouser":
    infoUsr($param, $twig, $conf_info_usr);
    break;

  case "what":
    what($param, $twig);
    break;

  case "infonav":
    infoNav($param, $twig);
    break;
}
?>
