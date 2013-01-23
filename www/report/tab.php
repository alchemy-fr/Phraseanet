<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . "/../../vendor/autoload.php";

/* get all the post parameters from report.php's form */


$request = http_request::getInstance();
$param = $request->get_parms(
    "dmin", // date minimal of the reporting
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
    "from", "printcsv", "docwhat"
);

$app = new Application();

$event = new GetResponseEvent($app, Request::createFromGlobals(), HttpKernelInterface::MASTER_REQUEST);

$app->addLocale($event);
$app->initSession($event);

$twig = $app['twig'];

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
    ( ! empty($param['order']) && ! empty($param['champ'])) ? $obj->setOrder($param['champ'], $param['order']) : "";
}

function doLimit($obj, $param)
{
    if ($obj->getEnableLimit())
        ( ! empty($param['page']) && ! empty($param['limit'])) ? $obj->setLimit($param['page'], $param['limit']) : $obj->setLimit(false, false);
}

function doFilter(Application $app, $obj, $param, $twig)
{
    $cor = $obj->getTransQueryString();
    $currentfilter = unserializeFilter($param['liste_filter']);

    $filter = new module_report_filter($app, $currentfilter, $cor);

    if ( ! empty($param['filter_column'])) {
        $field = getFilterField($param);
        $value = $param['filter_value'];

        if ($param['liste'] == "on") {
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

function doPreff(Application $app, $conf, $param)
{
    $conf_pref = array();
    $pref = module_report::getPreff($app, $param['sbasid']);
    foreach ($pref as $key => $field)
        $conf_pref[$field] = array($field, 0, 0, 0, 0);
    $conf = array_merge($conf, $conf_pref);

    return $conf;
}

function doReport(Application $app, $obj, $param, $conf, $twig, $what = false)
{
    if ($app['phraseanet.registry']->get('GV_anonymousReport') == true) {
        if (isset($conf['user']))
            unset($conf['user']);
        if (isset($conf['ip']))
            unset($conf['ip']);
    }
    //save initial conf
    $base_conf = $conf;
    //format conf according user preferences
    $conf = doUserConf($conf, $param);
    //display content of a table column when user click on it
    displayListColumn($base_conf, $param, $twig);
    //set order
    doOrder($obj, $param);

    //return a filter object
    $filter = doFilter($app, $obj, $param, $twig);

    //set new request filter if user asking for them
    if ($param['precise'] == 1)
        $filter->addFilter('xml', 'LIKE', $param['word']);
    elseif ($param['precise'] == 2)
        $filter->addFilter('record_id', '=', $param['word']);

    $tab_filter = $filter->getTabFilter(); //display filter in array
    $posting_filter = $filter->getPostingFilter(); //display filter in string to render it
    $active_column = $filter->getActiveColumn(); //get the column where a filter is applied
    //set filter to current obj
    $obj->setFilter($tab_filter);
    $obj->setActiveColumn($active_column);
    $obj->setPostingFilter($posting_filter);


    // display a new arraywhere results are group
    groupBy($obj, $param, $twig);
    //set Limit
    doLimit($obj, $param);

    //time to build our report
    if ( ! $what)
        $report = $obj->buildReport($conf);
    else
        $report = $obj->buildReport($conf, $what, $param['tbl']);

    return $report;
}

function doHtml($report, $param, $twig, $template, $type = false)
{
    $var = array(
        'result'      => isset($report['report']) ? $report['report'] : $report,
        'param'       => $param,
        'is_infouser' => false,
        'is_nav'      => false,
        'is_groupby'  => false,
        'is_plot'     => false,
        'is_doc'      => false
    );

    if ($type) {
        switch ($type) {
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
            case "doc" :
                $var['is_doc'] = true;
                break;
        }
    }

    return ($twig->render($template, $var));
}

function doCsv(Application $app, $obj, $param, $conf, $twig)
{
    $scv = "";
    //disable limit
    $obj->setHasLimit(false);
    $obj->setPrettyString(false);
    //construct new report
    doReport($app, $obj, $param, $conf, $twig);

    //get resulst
    $result_csv = $obj->getResult();

    $display = $obj->getDisplay();

    //convert array to csv string format
    try {
        $csv = format::arr_to_csv($result_csv, $display);
    } catch (Exception $e) {
        $csv = false;
    }

    return $csv;
}

function sendReport($html, $report = false, $title = false, $display_nav = false)
{
    if ($report) {
        $t = array(
            'rs'          => $html,
            'display_nav' => $report['display_nav'], // do we display the prev and next button ?
            'next'        => $report['next_page'], //Number of the next page
            'prev'        => $report['previous_page'], //Number of the previoous page
            'page'        => $report['page'], //The current page
            'filter'      => ((sizeof($report['filter']) > 0) ? serialize($report['filter']) : ""), //the serialized filters
            'col'         => $report['active_column'], //all the columns where a filter is applied
            'limit'       => $report['nb_record']
        );
    } else {
        $t = array(
            'rs'          => $html,
            'display_nav' => $display_nav,
            'title'       => $title
        );
    }
    echo p4string::jsonencode($t);
}

function sendCsv($csv)
{
    $t = array('rs' => $csv);
    echo p4string::jsonencode($t);
}

function getBasId($param)
{
    try {
        $record = new record_adapter(new Application(), $param['sbasid'], $param['rid']);

        return $record->get_base_id();
    } catch (Exception $e) {

    }

    return false;
}

function unserializeFilter($serialized_filter)
{
    $tab_filter = array();
    if ( ! empty($serialized_filter)) {
        $tab_filter = @unserialize(urldecode($serialized_filter));
    }

    return $tab_filter;
}

function doUserConf($conf, $param)
{
    if ( ! empty($param['list_column'])) {
        $new_conf = array();
        $new_conf = $conf;
        $x = explode(",", $param['list_column']);

        foreach ($conf as $key => $value) {
            if ( ! in_array($key, $x))
                unset($new_conf[$key]);
        }

        return $new_conf;
    }
    else {
        return $conf;
    }
}

function displayListColumn($conf, $param, $twig)
{
    if ($param['conf'] == "on") {
        $html = $twig->render('report/listColumn.html.twig', array(
            'conf'  => $conf,
            'param' => $param
            ));
        $t = array('liste' => $html, "title" => _("configuration"));
        echo p4string::jsonencode($t);
        exit();
    }
}

function groupBy($obj, $param, $twig, $on = false)
{
    //Contains  the name of the column where the group by is applied
    ( ! empty($param['groupby']) ? $groupby = explode(' ', $param['groupby']) : $groupby = false);
    //If users ask for group by, display the good array, result is encoded in Json , exit the function.
    if ($groupby) {
        $obj->setConfig(false);
        $report = $obj->buildReport(false, $groupby[0], $on);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig', 'group');
        $title = "Groupement des resultats sur le champ " . $report['display'][$report['allChamps'][0]]['title'];
        sendReport($html, false, $title);
        exit();
    }
}

function displayColValue($tab, $column, $twig, $on = false)
{
    $test = $twig->render('report/colFilter.html.twig', array(
        'result' => $tab,
        'field'  => $column
        ));
    $t = array('diag'  => $test, "title" => sprintf(_("filtrer les resultats sur la colonne %s"), $column));
    echo p4string::jsonencode($t);
    exit();
}

function getHistory(Application $app, $obj, $param, $twig, $conf, $dl = false, $title)
{
    $filter = doFilter($app, $obj, $param, $twig);

    if ( ! empty($param['user']) && empty($param['on']))
        $filter->addfilter('usrid', '=', $param['user']);
    elseif ( ! empty($param['on']) && ! empty($param['user']))
        $filter->addfilter($param['on'], '=', $param['user']);
    if ($dl) {
        $obj->setIsInformative(true);
    }
    $tab_filter = $filter->getTabFilter();
    $obj->setFilter($tab_filter);

    $obj->setOrder('ddate', 'DESC');
    $obj->setConfig(false);
    $obj->setTitle($title);
    $obj->setHasLimit(false);


    if ($param['printcsv'] == "on") {
        $csv = doCsv($app, $obj, $param, $conf, $twig);
        sendCsv($csv);
    } else {
        $report = $obj->buildReport($conf);
    }

    if ($dl) {
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig', "user");
    } else {
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
    }
    $request = $obj->getReq();

    return(array('html'   => $html, 'req'    => $request, 'params' => $obj->getParams()));
}
################################################ACTION FUNCTIONS#######################################################

function cnx(Application $app, $param, $twig)
{
    $cnx = new module_report_connexion($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

    $conf = array(
        'user' => array(_('phraseanet::utilisateurs'), 1, 1, 1, 1),
        'ddate' => array(_('report:: date'), 1, 0, 1, 1),
        'ip' => array(_('report:: IP'), 1, 0, 0, 0),
        'appli' => array(_('report:: modules'), 1, 0, 0, 0),
        'fonction' => array(_('report::fonction'), 1, 1, 1, 1),
        'activite' => array(_('report::activite'), 1, 1, 1, 1),
        'pays' => array(_('report::pays'), 1, 1, 1, 1),
        'societe' => array(_('report::societe'), 1, 1, 1, 1)
    );

    if ($param['printcsv'] == "on") {
        $csv = doCsv($app, $cnx, $param, $conf, $twig);
        sendCsv($csv);
    } else {
        $report = doReport($app, $cnx, $param, $conf, $twig);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html, $report);
    }
}
/* generate all the html string to display all the valid download in <table></table>, the result is encoded in json */

function gen(Application $app, $param, $twig)
{
    $dl = new module_report_download($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $conf = array(
        'user' => array(_('report:: utilisateurs'), 1, 1, 1, 1),
        'ddate' => array(_('report:: date'), 1, 0, 1, 1),
        'record_id' => array(_('report:: record id'), 1, 1, 1, 1),
        'final' => array(_('phrseanet:: sous definition'), 1, 0, 1, 1),
        'coll_id' => array(_('report:: collections'), 1, 0, 1, 1),
        'comment' => array(_('report:: commentaire'), 1, 0, 0, 0),
        'fonction' => array(_('report:: fonction'), 1, 1, 1, 1),
        'activite' => array(_('report:: activite'), 1, 1, 1, 1),
        'pays' => array(_('report:: pays'), 1, 1, 1, 1),
        'societe' => array(_('report:: societe'), 1, 1, 1, 1)
    );
    $conf = doPreff($app, $conf, $param);

    if ($param['printcsv'] == "on") {
        $csv = doCsv($app, $dl, $param, $conf, $twig);
        sendCsv($csv);
    } else {
        $report = doReport($app, $dl, $param, $conf, $twig);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html, $report);
    }
}
/* generate all the html string to display all the valid question in <table></table>, the result is encoded in json */

function ask(Application $app, $param, $twig)
{
    $ask = new module_report_question($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $conf = array(
        'user' => array(_('report:: utilisateur'), 1, 1, 1, 1),
        'search' => array(_('report:: question'), 1, 0, 1, 1),
        'ddate' => array(_('report:: date'), 1, 0, 1, 1),
        'fonction' => array(_('report:: fonction'), 1, 1, 1, 1),
        'activite' => array(_('report:: activite'), 1, 1, 1, 1),
        'pays' => array(_('report:: pays'), 1, 1, 1, 1),
        'societe' => array(_('report:: societe'), 1, 1, 1, 1)
    );

    if ($param['printcsv'] == "on") {
        $csv = doCsv($app, $ask, $param, $conf, $twig);
        sendCsv($csv);
    } else {
        $report = doReport($app, $ask, $param, $conf, $twig);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html, $report);
    }
}
/* generate the html code to display the download by doc (records or string in xml description), the result is encoded in json */

function doc(Application $app, $param, $twig)
{
    $dl = new module_report_download($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $conf = array(
        'telechargement' => array(_('report:: telechargements'), 1, 0, 0, 0),
        'record_id' => array(_('report:: record id'), 1, 1, 1, 0),
        'final' => array(_('phraseanet:: sous definition'), 1, 0, 1, 1),
        'file' => array(_('report:: fichier'), 1, 0, 0, 1),
        'mime' => array(_('report:: type'), 1, 0, 1, 1),
        'size' => array(_('report:: taille'), 1, 0, 1, 1)
    );
    $conf = doPreff($app, $conf, $param);

    if ($param['printcsv'] == "on") {
        $csv = doCsv($app, $dl, $param, $conf, $twig);
        sendCsv($csv);
    } else {
        $report = doReport($app, $dl, $param, $conf, $twig, 'record_id');
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig', 'doc');
        sendReport($html, $report);
    }
}
/* generate the html string to display the result from different report (see below) in <table></table>, the result is encoded in json */

function cnxb(Application $app, $param, $twig)
{
    $nav = new module_report_nav($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
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
        'nav'   => $nav->buildTabNav($conf_nav),
        'os'    => $nav->buildTabOs($conf_os),
        'res'   => $nav->buildTabRes($conf_res),
        'mod'   => $nav->buildTabModule($conf_mod),
        'combo' => $nav->buildTabCombo($conf_combo)
    );

    if ($param['printcsv'] == "on") {
        $csv = array(
            'nav'   => format::arr_to_csv($report['nav']['result'], $conf_nav),
            'os'    => format::arr_to_csv($report['os']['result'], $conf_os),
            'res'   => format::arr_to_csv($report['res']['result'], $conf_res),
            'mod'   => format::arr_to_csv($report['mod']['result'], $conf_mod),
            'combo' => format::arr_to_csv($report['combo']['result'], $conf_combo)
        );

        sendCsv($csv);
    } else {
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig', 'nav');
        sendReport($html);
    }
}
/* generate the html string to display the number of connexion by user in <table></table>, the result is encoded in json */

function cnxu(Application $app, $param, $twig)
{
    $connex = new module_report_activity($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $connex->setConfig(false);
    $connex->setBound("user", true);
    doLimit($connex, $param);

    if ($param['printcsv'] == "on") {
        $connex->setHasLimit(false);
        $report_csv = $connex->getConnexionBase(false, $param['on']);
        $result_csv = $connex->getResult();
        $display = $connex->getDisplay();
        $csv = format::arr_to_csv($result_csv, $display);
        sendCsv($csv);
    } else {
        $report = $connex->getConnexionBase(false, $param['on']);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html);
    }
}
/* generate all the html string to display the top 20 question by databox in <table></table>, the result is encoded in json */

function bestOf(Application $app, $param, $twig)
{
    $conf = array(
        'search' => array(_('report:: question'), 0, 0, 0, 0),
        'nb' => array(_('report:: nombre'), 0, 0, 0, 0),
        'nb_rep' => array(_('report:: nombre de reponses'), 0, 0, 0, 0)
    );
    $activity = new module_report_activity($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

    $activity->setLimit(1, $param['limit']);
    $activity->setTop(20);
    $activity->setConfig(false);


    if ($param['printcsv'] == "on") {
        $activity->setHasLimit(false);
        $report_csv = $activity->getTopQuestion($conf);
        $result_csv = $activity->getResult();
        $display = $activity->getDisplay();
        $csv = format::arr_to_csv($result_csv, $display);
        sendCsv($csv);
    } else {
        $report = $activity->getTopQuestion($conf);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html);
    }
}
/* generate all the html string to display all the resot of questions <table></table>, the result is encoded in json */

function noBestOf(Application $app, $param, $twig)
{
    $conf = array('search' => array(_('report:: question'), 0, 0, 0, 0),
        'nb' => array(_('report:: nombre'), 0, 0, 0, 0),
        'nb_rep' => array(_('report:: nombre de reponses'), 0, 0, 0, 0)
    );
    $activity = new module_report_activity($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $activity->setConfig(false);
    doLimit($activity, $param);


    if ($param['printcsv'] == "on") {
        $activity->setHasLimit(false);
        $report_csv = $activity->getTopQuestion($conf, true);
        $result_csv = $activity->getResult();
        $display = $activity->getDisplay();
        $csv = format::arr_to_csv($result_csv, $display);
        sendCsv($csv);
    } else {
        $report = $activity->getTopQuestion($conf, true);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html);
    }
}
/* generate all the html string to display the users connexions activity by hour in <table></table>, the result is encoded in json */

function tableSiteActivityPerHours(Application $app, $param, $twig)
{
    $activity = new module_report_activity($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $activity->setConfig(false);
    $report = $activity->getActivityPerHours();

    if ($param['printcsv'] == "on") {
        $display = $activity->getDisplay();
        $result = $activity->getResult();
        $csv = format::arr_to_csv($result, $display);
        sendCsv($csv);
    } else {
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig', 'plot');
        sendReport($html);
    }
}
/* generate all the html string to display all number of download day by day in <table></table>, the result is encoded in json */

function day(Application $app, $param, $twig)
{
    $conf = array('ddate' => array(_('report:: jour'), 0, 0, 0, 0),
        'total' => array(_('report:: total des telechargements'), 0, 0, 0, 0),
        'preview' => array(_('report:: preview'), 0, 0, 0, 0),
        'document' => array(_('report:: document original'), 0, 0, 0, 0)
    );
    $activity = new module_report_activity($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    doLimit($activity, $param);
    $activity->setConfig(false);

    if ($param['printcsv'] == "on") {
        $activity->setHasLimit(false);
        $report_csv = $activity->getDownloadByBaseByDay($conf);
        $result_csv = $activity->getResult();
        $display = $activity->getDisplay();
        $csv = format::arr_to_csv($result_csv, $display);
        sendCsv($csv);
    } else {
        $report = $activity->getDownloadByBaseByDay($conf);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html);
    }
}
/* generate all the html string to display all the details of download user by user in <table></table>, the result is encoded in json */

function usr(Application $app, $param, $twig)
{
    $conf = array('user' => array(_('report:: utilisateur'), 0, 1, 0, 0),
        'nbdoc' => array(_('report:: nombre de documents'), 0, 0, 0, 0),
        'poiddoc' => array(_('report:: poids des documents'), 0, 0, 0, 0),
        'nbprev' => array(_('report:: nombre de preview'), 0, 0, 0, 0),
        'poidprev' => array(_('report:: poids des previews'), 0, 0, 0, 0)
    );

    $activity = new module_report_activity($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    doLimit($activity, $param);
    $activity->setConfig(false);


    if ($param['printcsv'] == "on") {
        $activity->setHasLimit(false);
        $report_csv = $activity->getDetailDownload($conf, $param['on']);
        $result_csv = $activity->getResult();
        $display = $activity->getDisplay();
        $csv = format::arr_to_csv($result_csv, $display);
        sendCsv($csv);
    } else {
        $report = $activity->getDetailDownload($conf, $param['on']);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html);
    }
}
/* Display basic informations about an user */

function infoUsr(Application $app, $param, $twig, $conf)
{
    if ($app['phraseanet.registry']->get('GV_anonymousReport') == true) {
        $conf['conf'] = array(
            $param['on'] => array($param['on'], 0, 0, 0, 0),
            'nb' => array(_('report:: nombre'), 0, 0, 0, 0)
        );
    }

    $request = "";
    $params = array();
    $html = "";
    $html_info = "";
    $is_dl = false;

    if ($param['from'] == 'CNXU' || $param['from'] == 'CNX') {
        $histo = new module_report_connexion($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
        $conf_array = $conf['config_cnx'];
        $title = _("report:: historique des connexions");
    } elseif ($param['from'] == "USR" || $param['from'] == "GEN") {
        $histo = new module_report_download($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
        $conf_array = $conf['config_dl'];
        $is_dl = true;
        $title = _("report:: historique des telechargements");
    } elseif ($param['from'] == "ASK") {
        $histo = new module_report_question($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
        $conf_array = $conf['config_ask'];
        $title = _("report:: historique des questions");
    }

    if (isset($histo)) {
        $rs = getHistory($app, $histo, $param, $twig, $conf_array, $is_dl, $title);
        $html = $rs['html'];
        $request = $rs['req'];
        $params = $rs['params'];
    }

    $info = new module_report_nav($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $info->setPeriode("");
    $info->setCsv(false);
    $report = $info->buildTabGrpInfo($request, $params, $param['user'], $conf['conf'], $param['on']);
    if ($app['phraseanet.registry']->get('GV_anonymousReport') == false) {
        $html_info .= doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        (empty($param['on']) && isset($report['result'])) ? $title = $report['result'][0]['identifiant'] : $title = $param['user'];
    }
    else
        $title = $param['user'];

    sendReport($html_info . $html, false, $title);
}
/* Display some basics informations about a Document */

function what(Application $app, $param, $twig)
{
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
        'comment' => array(_('report:: commentaire'), 0, 0, 0, 0),
        'fonction' => array(_('report:: fonction'), 0, 0, 0, 0),
        'activite' => array(_('report:: activite'), 0, 0, 0, 0),
        'pays' => array(_('report:: pays'), 0, 0, 0, 0),
        'societe' => array(_('report:: societe'), 0, 0, 0, 0)
    );

    $config_dl = doUserConf($config_dl, $param);

    $html = "";
    $basid = getBasId($param);

    $what = new module_report_nav($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $what->setPeriode("");
    $what->setCsv(false);
    $what->setPrint(false);
    $report = $what->buildTabUserWhat($basid, $param['rid'], $config);
    $title = $what->getTitle();

    $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');

    if ($param['from'] == 'TOOL') {
        $what->setTitle("");
        sendReport($html, false, $title);

        return false;
    } elseif ($param['from'] != 'DASH' && $param['from'] != "PUSHDOC") {
        $histo = new module_report_download($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

        $filter = doFilter($app, $histo, $param, $twig);
        if ( ! empty($param['rid']))
            $filter->addfilter('record_id', '=', $param['rid']);

        $tab_filter = $filter->getTabFilter();
        $histo->setFilter($tab_filter);
        $histo->setOrder('ddate', 'DESC');
        $histo->setTitle(_("report:: historique des telechargements"));
        $histo->setConfig(false);
        if ($param['printcsv'] == "on") {
            $csv = doCsv($app, $histo, $param, $config_dl, $twig);
            sendCsv($csv);
        } else {
            $report = $histo->buildReport($config_dl);
            $html .= doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
            sendReport($html, false, $title);
        }
    } elseif ($app['phraseanet.registry']->get('GV_anonymousReport') == false && $param['from'] != 'DOC' && $param['from'] != 'DASH' && $param['from'] != "GEN" && $param['from'] != "PUSHDOC") {
        $conf = array(
            'identifiant' => array(_('report:: identifiant'), 0, 0, 0, 0),
            'nom' => array(_('report:: nom'), 0, 0, 0, 0),
            'mail' => array(_('report:: email'), 0, 0, 0, 0),
            'adresse' => array(_('report:: adresse'), 0, 0, 0, 0),
            'tel' => array(_('report:: telephone'), 0, 0, 0, 0)
        );
        $info = new module_report_nav($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
        $info->setPeriode("");
        $info->setConfig(false);
        $info->setTitle(_('report:: utilisateur'));

        if ($param['printcsv'] == "on") {
            $csv = doCsv($info, $param, $conf, $twig);
            sendCsv($csv);
        } else {
            $report = $info->buildTabGrpInfo(false, array(), $param['user'], $conf, false);
            $html .= doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
            sendReport($html, false, $title);
        }
    }
    else
        sendReport($html, false, $title);
}
/* Display informations about navigators */

function infoNav(Application $app, $param, $twig)
{
    $conf = array(
        'version' => array(_('report::version '), 0, 0, 0, 0),
        'nb' => array(_('report:: nombre'), 0, 0, 0, 0)
    );
    $infonav = new module_report_nav($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);
    $infonav->setCsv(false);
    $infonav->setConfig(false);
    $report = $infonav->buildTabInfoNav($conf, $param['user']);
    $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
    sendReport($html, false, $param['user']);
}

function pushDoc(Application $app, $param, $twig)
{
    $conf = array(
        'user' => array("", 1, 0, 1, 1),
        'getter' => array("Destinataire", 1, 0, 1, 1),
        'date' => array("", 1, 0, 1, 1),
        'record_id' => array("", 1, 1, 1, 1),
        'file' => array("", 1, 0, 1, 1),
        'mime' => array("", 1, 0, 1, 1),
    );
    $dl = new module_report_push($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

    $dl->setConfig(false);
    if ($param['printcsv'] == "on") {
        $csv = doCsv($app, $dl, $param, $conf, $twig);
        sendCsv($csv);
    } else {
        $report = doReport($app, $dl, $param, $conf, $twig);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html, $report);
    }
}

function addDoc(Application $app, $param, $twig)
{
    $conf = array(
        'user' => array("", 1, 0, 1, 1),
        'date' => array("", 1, 0, 1, 1),
        'record_id' => array("", 1, 1, 1, 1),
        'file' => array("", 1, 0, 1, 1),
        'mime' => array("", 1, 0, 1, 1),
    );
    $dl = new module_report_add($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

    $dl->setConfig(false);
    if ($param['printcsv'] == "on") {
        $csv = doCsv($app, $dl, $param, $conf, $twig);
        sendCsv($csv);
    } else {
        $report = doReport($app, $dl, $param, $conf, $twig);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html, $report);
    }
}

function ediDoc(Application $app, $param, $twig)
{
    $conf = array(
        'user' => array("", 1, 0, 1, 1),
        'date' => array("", 1, 0, 1, 1),
        'record_id' => array("", 1, 1, 1, 1),
        'file' => array("", 1, 0, 1, 1),
        'mime' => array("", 1, 0, 1, 1),
    );
    $dl = new module_report_edit($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

    $dl->setConfig(false);
    if ($param['printcsv'] == "on") {
        $csv = doCsv($app, $dl, $param, $conf, $twig);
        sendCsv($csv);
    } else {
        $report = doReport($app, $dl, $param, $conf, $twig);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html, $report);
    }
}

function validDoc(Application $app, $param, $twig)
{
    $conf = array(
        'user' => array("", 1, 0, 1, 1),
        'getter' => array("Destinataire", 1, 0, 1, 1),
        'date' => array("", 1, 0, 1, 1),
        'record_id' => array("", 1, 1, 1, 1),
        'file' => array("", 1, 0, 1, 1),
        'mime' => array("", 1, 0, 1, 1),
    );
    $dl = new module_report_validate($app, $param['dmin'], $param['dmax'], $param['sbasid'], $param['collection']);

    $dl->setConfig(false);
    if ($param['printcsv'] == "on") {
        $csv = doCsv($app, $dl, $param, $conf, $twig);
        sendCsv($csv);
    } else {
        $report = doReport($app, $dl, $param, $conf, $twig);
        $html = doHtml($report, $param, $twig, 'report/ajax_data_content.html.twig');
        sendReport($html, $report);
    }
}

function whichDoc(Application $app, $param, $twig)
{
    switch ($param['docwhat']) {
        case "ADDDOC":
            addDoc($app, $param, $twig);
            break;

        case "EDIDOC":
            ediDoc($app, $param, $twig);
            break;

        case "PUSHDOC":
            pushDoc($app, $param, $twig);
            break;
        case "VALIDATEDOC" :
            validDoc($app, $param, $twig);
            break;
    }
}
################################################SWITCH ACTIONS##############################################################

switch ($param['tbl']) {
    case "CNX":
        cnx($app, $param, $twig);
        break;

    case "CNXU":
        cnxu($app, $param, $twig);
        break;

    case "CNXB":
        cnxb($app, $param, $twig);
        break;

    case "GEN":
        gen($app, $param, $twig);
        break;

    case "DAY":
        day($app, $param, $twig);
        break;

    case "DOC":
        doc($app, $param, $twig);
        break;

    case "BESTOF":
        bestOf($app, $param, $twig);
        break;

    case "NOBESTOF":
        noBestOf($app, $param, $twig);
        break;

    case "SITEACTIVITY":
        tableSiteActivityPerHours($app, $param, $twig);
        break;

    case "USR":
        usr($app, $param, $twig);
        break;

    case "ASK":
        ask($app, $param, $twig);
        break;

    case "infouser":
        infoUsr($app, $param, $twig, $conf_info_usr);
        break;

    case "what":
        what($app, $param, $twig);
        break;

    case "infonav":
        infoNav($app, $param, $twig);
        break;

    case "WDOC":
        whichDoc($app, $param, $twig);
        break;
}
?>
