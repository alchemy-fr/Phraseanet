<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . "/../../lib/bootstrap.php";

$app = new Application();
$request = http_request::getInstance();

$parm = $request->get_parms(
    "dmin"
    , "dmax"
    , "baslst"
    , "popbases"
    , "tbl"
    , "precise"
    , "preciseWord"
    , "preciseUser"
    , "page"
    , "limit"
    , "fonction"
    , "pays"
    , "societe"
    , "activite"
    , "on"
    , "docwhat"
);

extract($parm);

/* Initialise les dates par defaults min au 1er jour du mois courant et max a la date courante */
if ($parm['dmin'] == "")
    $parm['dmin'] = "01-" . date("m") . "-" . date("Y");
if ($parm['dmax'] == "")
    $parm['dmax'] = date("d") . "-" . date("m") . "-" . date("Y");

$td = explode("-", $parm['dmin']);
$parm['dmin'] = date('Y-m-d H:i:s', mktime(0, 0, 0, $td[1], $td[0], $td[2]));

$td = explode("-", $parm['dmax']);
$parm['dmax'] = date('Y-m-d H:i:s', mktime(23, 59, 59, $td[1], $td[0], $td[2]));

//get user's sbas & collections selection from popbases
$selection = array();
$popbases = array_fill_keys($popbases, 0);
$liste = '';
$i = 0;
$id_sbas = "";
foreach ($popbases as $key => $val) {
    $exp = explode("_", $key);
    if ($exp[0] != $id_sbas && $i != 0) {
        $selection[$id_sbas]['liste'] = $liste;
        $liste = '';
    }
    $selection[$exp[0]][] = $exp[1];
    $liste .= ( empty($liste) ? '' : ',') . $exp[1];
    $id_sbas = $exp[0];
    $i ++;
}
//fill the last entry
$selection[$id_sbas]['liste'] = $liste;

$twig = $app['twig'];

echo $twig->render(
    'report/ajax_report_content.html.twig', array(
    'selection' => $selection,
    'param'     => $parm,
    'anonymous' => $app['phraseanet.registry']->get('GV_anonymousReport'),
    'ajax'      => true
    )
);

